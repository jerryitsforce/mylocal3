<?php

namespace Branch8\HotaiPoint\Console\Command;

use Magento\Framework\App\State;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Api\OrderItemRepositoryInterface as OrderItemRepository;
use Magento\Sales\Model\Order\Item as OrderItem;

class addDeductionPointMemo extends Command
{
    const OPTION_SALES_ORDER_ITEM_ID = 'sales_order_item_id';
    const OPTION_QUOTE_ITEM_ID = 'quote_item_id';
    const OPTION_MESSAGE = 'message';

    /** @var State */
    protected $state;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var ResourceConnection */
    protected $resourceConnection;

    /** @var OrderItemRepository */
    protected $orderItemRepository;

    protected $salesOrderItemId;
    protected $quoteItemId;
    protected $message;

    public function __construct(
        State $state,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        ResourceConnection $resourceConnection,
        OrderItemRepository $orderItemRepository
    ) {
        $this->state                 = $state;
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
        $this->resourceConnection    = $resourceConnection;
        $this->orderItemRepository    = $orderItemRepository;

        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('hotai_point:addDeductionPointMemo');
        $this->setDescription('Add memo to hotai_point_deduction_point_memo. Pass in --sales_order_item_id=123 or --quote_item_id=456, and --message="your message".');

        $this->addOption(
            self::OPTION_SALES_ORDER_ITEM_ID,
            null,
            InputOption::VALUE_OPTIONAL,
            'input sales_order_item_id like this: --sales_order_item_id=123'
        );

        $this->addOption(
            self::OPTION_QUOTE_ITEM_ID,
            null,
            InputOption::VALUE_OPTIONAL,
            'input quote_item_id like this: --quote_item_id=456'
        );

        $this->addOption(
            self::OPTION_MESSAGE,
            null,
            InputOption::VALUE_REQUIRED,
            'input message like this: --message="your message"'
        );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB);

        $this->checkInputParameter($input);

        $taiwanDateObj = $this->hotaiCoreCommonHelper->getTaiwanDateTimeObject();

        $memoData = [
            "Title"           => "Memo added by addDeductionPointMemo command.",
            "Taiwan Datetime" => $taiwanDateObj->format("Y-m-d H:i:s"),
            "Message"         => $this->message,
        ];

        try {
            if (!empty($this->salesOrderItemId)) {
                $this->updateOrderItemMemo((int) $this->salesOrderItemId, $memoData);
                $output->writeln("<info>Successfully added memo to sales_order_item ID: {$this->salesOrderItemId}</info>");
            } elseif (!empty($this->quoteItemId)) {
                $this->updateQuoteItemMemo((int) $this->quoteItemId, $memoData);
                $output->writeln("<info>Successfully added memo to quote_item ID: {$this->quoteItemId}</info>");
            }

            return Command::SUCCESS;
        } catch (\Throwable $th) {
            $output->writeln("<error>Failed to add memo: " . $th->getMessage() . "</error>");
            return Command::FAILURE;
        }
    }

    /**
     * 檢查傳入參數
     * @return void
     */
    protected function checkInputParameter(InputInterface $input): void
    {
        $this->salesOrderItemId = $input->getOption(self::OPTION_SALES_ORDER_ITEM_ID);
        $this->quoteItemId       = $input->getOption(self::OPTION_QUOTE_ITEM_ID);
        $this->message           = $input->getOption(self::OPTION_MESSAGE);

        if (empty($this->salesOrderItemId) && empty($this->quoteItemId)) {
            throw new \Exception("Either " . self::OPTION_SALES_ORDER_ITEM_ID . " or " . self::OPTION_QUOTE_ITEM_ID . " must be provided.");
        }

        if (!empty($this->salesOrderItemId) && !empty($this->quoteItemId)) {
            throw new \Exception("Only one of " . self::OPTION_SALES_ORDER_ITEM_ID . " or " . self::OPTION_QUOTE_ITEM_ID . " should be provided, not both.");
        }

        if (empty($this->message)) {
            throw new \Exception("Input " . self::OPTION_MESSAGE . " is mandatory.");
        }
    }

    /**
     * 更新 sales_order_item 的 memo
     * @param int $orderItemId
     * @param array $memoData
     * @return void
     */
    protected function updateOrderItemMemo(int $orderItemId, array $memoData): void
    {
        /** @var OrderItem $orderItem */
        $orderItem = $this->orderItemRepository->get($orderItemId);

        $currentMemo = $orderItem->getData("hotai_point_deduction_point_memo") ?? "";
        $updatedMemo = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate($currentMemo, $memoData);

        $orderItem->setData('hotai_point_deduction_point_memo', $updatedMemo);
        $this->orderItemRepository->save($orderItem);
    }

    /**
     * 更新 quote_item 的 memo
     * @param int $quoteItemId
     * @param array $memoData
     * @return void
     */
    protected function updateQuoteItemMemo(int $quoteItemId, array $memoData): void
    {
        $connection = $this->resourceConnection->getConnection();

        // 先取得現有的 memo
        $query = $connection->select()
            ->from($this->resourceConnection->getTableName('quote_item'), ['hotai_point_deduction_point_memo'])
            ->where('item_id = ?', $quoteItemId);

        $result = $connection->fetchRow($query);

        if (!$result) {
            throw new \Exception("Quote item with ID {$quoteItemId} not found.");
        }

        $currentMemo = $result['hotai_point_deduction_point_memo'] ?? "";
        $updatedMemo = $this->hotaiCoreCommonHelper->prepareMemoStringForUpdate($currentMemo, $memoData);

        try {
            $connection->beginTransaction();

            $table = $connection->getTableName('quote_item');

            $connection->update(
                $table,
                ['hotai_point_deduction_point_memo' => $updatedMemo],
                ['item_id = ?' => $quoteItemId]
            );

            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }
}

