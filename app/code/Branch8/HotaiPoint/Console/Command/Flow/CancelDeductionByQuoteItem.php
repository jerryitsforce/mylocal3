<?php

namespace Branch8\HotaiPoint\Console\Command\Flow;

use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Branch8\HotaiPoint\Helper\Api as ApiHelper;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\HotaiPoint\Helper\Common as CommonHelper;
use Magento\Framework\App\ResourceConnection;

class CancelDeductionByQuoteItem extends Command
{
    const LOG_FOLDER_NAME = 'HotaiPoint/Console/Command/Flow/CancelDeductionByQuoteItem';

    const OPTION_QUOTE_ITEM_ID = 'quote_item_id';

    /** @var State */
    protected $state;

    /** @var ApiHelper */
    protected $apiHelper;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var ResourceConnection */
    protected $resourceConnection;

    protected $quoteItemId;
    protected $walkthroughLog;

    public function __construct(
        State $state,
        ApiHelper $apiHelper,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        ResourceConnection $resourceConnection
    ) {
        $this->state                 = $state;
        $this->apiHelper             = $apiHelper;
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
        $this->resourceConnection    = $resourceConnection;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('hotai_point:flow:CancelDeductionByQuoteItem');
        $this->setDescription('Pass in --quote_item_id=123, will execute cancel deduction flow for one quote item.');

        $this->addOption(
            self::OPTION_QUOTE_ITEM_ID,
            null,
            InputOption::VALUE_REQUIRED,
            'input quote_item_id like this: --quote_item_id=123'
        );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB);

        $this->checkInputParameter($input);

        $quoteItem = $this->getQuoteItem((int) $this->quoteItemId);

        if (!$this->checkItemData($quoteItem)) {
            throw new \Exception("item data for cancel deduction error.");
        }

        try {
            $this->walkthroughLog = [];

            $traceNo              = $quoteItem["hotai_point_deduction_point_trace_no"];

            $this->walkthroughLog[] = "Ready to request Cancel API.";
            $cancelResponse         = $this->apiHelper->requestApiCancel($quoteItem["customer_id"], $traceNo);
            $this->walkthroughLog[] = "Cancel API done, request data: " . $this->apiHelper->getRequestDataString();
            $this->walkthroughLog[] = "Cancel API done, response data: " . json_encode($cancelResponse);

            $this->quoteItemUpdateIfSuccess($quoteItem);

            $output->writeln("<info>Flow success.</info>");

            return Command::SUCCESS;
        } catch (\Throwable $th) {
            $this->walkthroughLog[] = "Exception, last API request data: " . $this->apiHelper->getRequestDataString();
            $this->quoteItemUpdateIfFail($quoteItem, $th->getMessage());

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
        $this->quoteItemId = (int) $input->getOption(self::OPTION_QUOTE_ITEM_ID);

        if (empty($this->quoteItemId)) {
            throw new \Exception("Input " . self::OPTION_QUOTE_ITEM_ID . " is mandatory.");
        }
    }

    protected function getQuoteItem(int $quoteItemId)
    {
        $connection = $this->resourceConnection->getConnection();

        $query = $connection->select()
            ->from($this->resourceConnection->getTableName('quote_item'))
            ->join('quote', 'quote_item.quote_id=quote.entity_id', ['customer_id'])
            ->where('item_id = ?', $quoteItemId);

        $result = $connection->fetchRow($query);

        if (!$result) {
            throw new \Exception("Quote item with ID {$quoteItemId} not found.");
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

    protected function quoteItemUpdateIfSuccess($quoteItem, string $message = ""): void
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

            $table = $connection->getTableName('quote_item');

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

    protected function quoteItemUpdateIfFail($quoteItem, string $message): void
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

            $table = $connection->getTableName('quote_item');

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
