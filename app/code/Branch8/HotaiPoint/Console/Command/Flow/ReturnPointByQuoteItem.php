<?php

namespace Branch8\HotaiPoint\Console\Command\Flow;

use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Branch8\HotaiPoint\Helper\Api as ApiHelper;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Magento\Framework\App\ResourceConnection;

class ReturnPointByQuoteItem extends Command
{
    const LOG_FOLDER_NAME = 'HotaiPoint/Console/Command/Flow/ReturnPointByQuoteItem';

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
        $this->setName('hotai_point:flow:ReturnPointByQuoteItem');
        $this->setDescription('Pass in --quote_item_id=123, will execute return point flow for one quote item.');

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

        if (!$this->checkQuoteItemData($quoteItem)) {
            throw new \Exception("quote item data for return point error.");
        }

        $getPointResponse  = $this->apiHelper->requestApiGetPointByOneid((int) $quoteItem["customer_id"]);
        $pointBeforeReturn = $this->apiHelper->getPointFromResponse($getPointResponse);

        try {
            $returnPointResponse             = $this->apiHelper->requestApiReturnPoint(
                $quoteItem["customer_id"],
                $quoteItem["hotai_point_deduction_point_trans_s_n"],
                $quoteItem["hotai_point_deduction_point_trans_datetime"],
                $quoteItem["hotai_point_deduction_point_trace_no"]
            );
            $returnPointRequest              = $this->apiHelper->getRequestDataArray();
            $returnPointSuccessTaiwanDateObj = $this->hotaiCoreCommonHelper->getTaiwanDateTimeObject();

            $returnPointTraceNo = $this->apiHelper->getTraceNoFromResponse($returnPointResponse);
            $pointAfterReturn   = $this->apiHelper->getPointFromResponse($returnPointResponse);
            $pointDescription   = "Point before return: {$pointBeforeReturn}, point after return: {$pointAfterReturn}";

            $returnPointMemo = [
                "Title"                 => "ReturnPoint execute by ReturnPointByQuoteItem command manually success.",
                "Taiwan Datetime"       => $returnPointSuccessTaiwanDateObj->format("Y-m-d H:i:s"),
                "Return point request"  => $returnPointRequest,
                "Return point response" => $returnPointResponse,
                "Point description"     => $pointDescription,
            ];

            $quoteItemDescription = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
                $quoteItem["hotai_point_deduction_point_memo"] ?? "",
                $returnPointMemo
            );

            $this->updateQuoteItem($quoteItem, [
                // 'hotai_point_return_point_trace_no' => $returnPointTraceNo,
                // 'hotai_point_return_point_trans_datetime' => $returnPointSuccessTaiwanDateObj->format("Y-m-d H:i:s"),
                'hotai_point_deduction_point_memo' => $quoteItemDescription,
            ]);

            $output->writeln("<info>Flow success.</info>");

            return Command::SUCCESS;
        } catch (\Throwable $th) {
            $taiwanDateObj = $this->hotaiCoreCommonHelper->getTaiwanDateTimeObject();

            $newDesciptionArray = [
                "Title"           => "ReturnPoint execute by ReturnPointByQuoteItem command manually exception.",
                "Taiwan Datetime" => $taiwanDateObj->format("Y-m-d H:i:s"),
                "Last Request"    => $this->apiHelper->getRequestDataString(),
                "Message"         => $th->getMessage()
            ];

            $quoteItemDescription = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate(
                $quoteItem["hotai_point_deduction_point_memo"] ?? "",
                $newDesciptionArray
            );

            $this->updateQuoteItem($quoteItem, [
                'hotai_point_deduction_point_memo' => $quoteItemDescription,
            ]);

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

    protected function checkQuoteItemData($quoteItem): bool
    {
        if (empty($quoteItem["hotai_point_deduction_point_trace_no"])) {
            return false;
        }

        if (empty($quoteItem["hotai_point_deduction_point_trans_s_n"])) {
            return false;
        }

        if (empty($quoteItem["hotai_point_deduction_point_trans_datetime"])) {
            return false;
        }

        return true;
    }

    protected function updateQuoteItem($quoteItem, array $data): void
    {
        try {
            $connection = $this->resourceConnection->getConnection();

            $connection->beginTransaction();

            $table = $connection->getTableName('quote_item');

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
}

