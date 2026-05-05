<?php

namespace Branch8\HotaiPoint\Console\Command\Flow;

use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Branch8\HotaiPoint\Helper\Api as ApiHelper;
use Magento\Sales\Api\OrderItemRepositoryInterface as OrderItemRepository;
use Magento\Sales\Model\Order\Item as OrderItem;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;

class ReturnPointByOrderItem extends Command
{
    const LOG_FOLDER_NAME = 'HotaiPoint/Console/Command/Flow/ReturnPointByOrderItem';

    const OPTION_ORDER_ITEM_ID = 'order_item_id';

    /** @var State */
    protected $state;

    /** @var ApiHelper */
    protected $apiHelper;

    /** @var OrderItemRepository */
    protected $orderItemRepository;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    protected $orderItemId;

    public function __construct(
        State $state,
        ApiHelper $apiHelper,
        OrderItemRepository $orderItemRepository,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper
    ) {
        $this->state                 = $state;
        $this->apiHelper             = $apiHelper;
        $this->orderItemRepository   = $orderItemRepository;
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('hotai_point:flow:ReturnPointByOrderItem');
        $this->setDescription('Pass in --order_item_id=123, will execute return point flow for one sales order item.');

        $this->addOption(
            self::OPTION_ORDER_ITEM_ID,
            null,
            InputOption::VALUE_REQUIRED,
            'input order_item_id like this: --order_item_id=123'
        );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB);

        $this->checkInputParameter($input);

        $orderItem = $this->orderItemRepository->get((int) $this->orderItemId);

        if (!$this->checkOrderItemData($orderItem)) {
            throw new \Exception("order item data for return point error.");
        }

        $getPointResponse  = $this->apiHelper->requestApiGetPointByOneid((int) $orderItem->getOrder()->getCustomerId());
        $pointBeforeReturn = $this->apiHelper->getPointFromResponse($getPointResponse);
        // $returnFailurePointInfo = $this->apiHelper->getReturnFailurePointInfoByOrderItemId((int) $orderItem->getId());

        try {
            $returnPointResponse             = $this->apiHelper->requestApiReturnPointByOrderItemId((int) $orderItem->getId());
            $returnPointRequest              = $this->apiHelper->getRequestDataArray();
            $returnPointSuccessTaiwanDateObj = $this->getTaiwanDateObject();

            $returnPointTraceNo = $this->apiHelper->getTraceNoFromResponse($returnPointResponse);
            $pointAfterReturn   = $this->apiHelper->getPointFromResponse($returnPointResponse);
            $pointDescription   = "Point before return: {$pointBeforeReturn}, point after return: {$pointAfterReturn}";

            $returnPointMemo = [
                "Title"                 => "ReturnPoint execute by command manually success.",
                "Taiwan Datetime"       => $returnPointSuccessTaiwanDateObj->format("Y-m-d H:i:s"),
                "Return point request"  => $returnPointRequest,
                "Return point response" => $returnPointResponse,
                "Point description"     => $pointDescription,
            ];

            $orderItemDescription = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
                $orderItem->getData("hotai_point_deduction_point_memo") ?? "",
                $returnPointMemo
            );

            $orderItem->setData('hotai_point_return_point_trace_no', $returnPointTraceNo);
            $orderItem->setData('hotai_point_return_point_trans_datetime', $returnPointSuccessTaiwanDateObj->format("Y-m-d H:i:s"));
            $orderItem->setData('hotai_point_deduction_point_memo', $orderItemDescription);
            $this->orderItemRepository->save($orderItem);

            $output->writeln("<info>Flow success.</info>");

            return Command::SUCCESS;
        } catch (\Throwable $th) {
            $taiwanDateObj = $this->getTaiwanDateObject();

            $newDesciptionArray = [
                "Title"           => "ReturnPoint execute by command manually exception.",
                "Taiwan Datetime" => $taiwanDateObj->format("Y-m-d H:i:s"),
                "Last Request"    => $this->apiHelper->getRequestDataString(),
                "Message"         => $th->getMessage()
            ];

            $orderItemDescription = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
                $orderItem->getData(key: "hotai_point_deduction_point_memo") ?? "",
                $newDesciptionArray
            );

            $orderItem->setData('hotai_point_deduction_point_memo', $orderItemDescription);
            $this->orderItemRepository->save($orderItem);

            $output->writeln("<error>Flow fail, exception message: </error>");
            $output->writeln("<error>" . $th->getMessage() . "</error>");

            return Command::FAILURE;
        }
    }

    /**
     * 檢查傳入參數
     * @return void
     */
    protected function checkInputParameter(InputInterface $input): void
    {
        $this->orderItemId = (int) $input->getOption(self::OPTION_ORDER_ITEM_ID);

        if (empty($this->orderItemId)) {
            throw new \Exception("Input " . self::OPTION_ORDER_ITEM_ID . " is madatory.");
        }
    }

    protected function checkOrderItemData(OrderItem $orderItem): bool
    {
        if (empty($orderItem->getData("hotai_point_deduction_point_trace_no"))) {
            return false;
        }

        if (empty($orderItem->getData("hotai_point_deduction_point_trans_s_n"))) {
            return false;
        }

        if (empty($orderItem->getData("hotai_point_deduction_point_trans_datetime"))) {
            return false;
        }

        return true;
    }

    protected function getTaiwanDateObject(): \DateTime
    {
        $taiwanDateObj = new \DateTime();
        $taiwanDateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));

        return $taiwanDateObj;
    }
}
