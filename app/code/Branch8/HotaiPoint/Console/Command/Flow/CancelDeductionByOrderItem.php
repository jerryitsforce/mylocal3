<?php

namespace Branch8\HotaiPoint\Console\Command\Flow;

use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Branch8\HotaiPoint\Helper\Api as ApiHelper;
use Magento\Sales\Api\OrderItemRepositoryInterface as OrderItemRepository;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\HotaiPoint\Helper\Common as CommonHelper;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Api\OrderRepositoryInterface as OrderRepository;

class CancelDeductionByOrderItem extends Command
{
    const LOG_FOLDER_NAME = 'HotaiPoint/Console/Command/Flow/CancelDeductionByOrderItem';

    const OPTION_ORDER_ITEM_ID = 'order_item_id';

    /** @var State */
    protected $state;

    /** @var ApiHelper */
    protected $apiHelper;

    /** @var OrderItemRepository */
    protected $orderItemRepository;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var CommonHelper */
    protected $commonHelper;

    /** @var ResourceConnection */
    protected $resourceConnection;

    /** @var OrderRepository */
    protected $orderRepository;

    protected $orderItemId;
    protected $walkthroughLog;

    public function __construct(
        State $state,
        ApiHelper $apiHelper,
        OrderItemRepository $orderItemRepository,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        CommonHelper $commonHelper,
        ResourceConnection $resourceConnection,
        OrderRepository $orderRepository
    ) {
        $this->state                 = $state;
        $this->apiHelper             = $apiHelper;
        $this->orderItemRepository   = $orderItemRepository;
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
        $this->commonHelper          = $commonHelper;
        $this->resourceConnection    = $resourceConnection;
        $this->orderRepository       = $orderRepository;


        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('hotai_point:flow:CancelDeductionByOrderItem');
        $this->setDescription('Pass in --order_item_id=123, will execute cancel deduction flow for one quote item.');

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
        $order     = $this->orderRepository->get($orderItem->getOrderId());

        $this->commonHelper->syncHotaiPointFieldsFromQuoteItem($orderItem, $order, self::LOG_FOLDER_NAME);

        $orderItem = $this->getOrderItem((int) $this->orderItemId);

        if (!$this->checkItemData($orderItem)) {
            throw new \Exception("item data for cancel deduction error.");
        }

        try {
            $this->walkthroughLog = [];

            $traceNo = $orderItem["hotai_point_deduction_point_trace_no"];

            $this->walkthroughLog[] = "Ready to request Cancel API.";
            $cancelResponse         = $this->apiHelper->requestApiCancel($orderItem["customer_id"], $traceNo);
            $this->walkthroughLog[] = "Cancel API done, request data: " . $this->apiHelper->getRequestDataString();
            $this->walkthroughLog[] = "Cancel API done, response data: " . json_encode($cancelResponse);

            $this->orderItemUpdateIfSuccess($orderItem);

            $output->writeln("<info>Flow success.</info>");

            return Command::SUCCESS;
        } catch (\Throwable $th) {
            $this->walkthroughLog[] = "Exception, last API request data: " . $this->apiHelper->getRequestDataString();
            $this->orderItemUpdateIfFail($orderItem, $th->getMessage());

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
            throw new \Exception("Input " . self::OPTION_ORDER_ITEM_ID . " is mandatory.");
        }
    }

    protected function getOrderItem(int $orderItemId)
    {
        $connection = $this->resourceConnection->getConnection();

        $query = $connection->select()
            ->from($this->resourceConnection->getTableName('sales_order_item'))
            ->join('sales_order', 'sales_order_item.order_id=sales_order.entity_id', ['customer_id', 'quote_id'])
            ->where('item_id = ?', $orderItemId);

        $result = $connection->fetchRow($query);

        if (!$result) {
            throw new \Exception("Order item with ID {$orderItemId} not found.");
        }

        return $result;
    }

    protected function checkItemData($orderItem): bool
    {
        if (empty($orderItem["hotai_point_deduction_point_trace_no"])) {
            return false;
        }

        return true;
    }

    protected function orderItemUpdateIfSuccess($quoteItem, string $message = ""): void
    {
        $taiwanDateObject = $this->getTaiwanDateObject();

        $memoMessage = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
            $quoteItem['hotai_point_deduction_point_memo'],
            [
                "Timestamp"      => time(),
                "Datetime(+8)"   => $taiwanDateObject->format("Y-m-d H:i:s"),
                "Title"          => "Deduction point cancel execute by command manually success.",
                "Message"        => $message,
                "WalkthroughLog" => $this->walkthroughLog,
            ]
        );

        try {
            $connection = $this->resourceConnection->getConnection();

            $connection->beginTransaction();

            $table = $connection->getTableName('sales_order_item');

            $data = [
                "hotai_point_deduction_point_progress_status" => CommonHelper::DEDUCTION_POINT_PROGRESS_STATUS_TRANSACTION_CANCEL,
                "hotai_point_deduction_point_memo"            => $memoMessage
            ];

            $connection->update(
                $table,
                $data,
                ['item_id = ?' => $quoteItem["item_id"]]
            );

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    protected function orderItemUpdateIfFail($quoteItem, string $message): void
    {
        $taiwanDateObject = $this->getTaiwanDateObject();

        $memoMessage = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
            $quoteItem['hotai_point_deduction_point_memo'],
            [
                "Timestamp"      => time(),
                "Datetime(+8)"   => $taiwanDateObject->format("Y-m-d H:i:s"),
                "Title"          => "Deduction point cancel execute by command manually fail.",
                "Message"        => $message,
                "WalkthroughLog" => $this->walkthroughLog,
            ]
        );

        try {
            $connection = $this->resourceConnection->getConnection();

            $connection->beginTransaction();

            $table = $connection->getTableName('sales_order_item');

            $data = [
                "hotai_point_deduction_point_memo" => $memoMessage
            ];

            $connection->update(
                $table,
                $data,
                ['item_id = ?' => $quoteItem["item_id"]]
            );

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    protected function getTaiwanDateObject(): \DateTime
    {
        $taiwanDateObj = new \DateTime();
        $taiwanDateObj->setTimezone(new \DateTimeZone("Asia/Taipei"));

        return $taiwanDateObj;
    }
}
