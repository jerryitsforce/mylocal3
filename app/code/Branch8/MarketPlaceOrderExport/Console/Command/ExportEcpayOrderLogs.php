<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceOrderExport\Console\Command;

use Branch8\MarketPlaceOrderExport\Model\EcpayLogWriter;
use Branch8\MarketPlaceOrderExport\Model\Services\GetTZOffsetTransitions;
use Magento\Framework\App\Area;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Registry;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManager;
use Symfony\Component\Console\Helper\ProgressBarFactory;
use Branch8\MarketPlaceOrderExport\Helper\Logger as LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


/**
 * Class ProductAttributesCleanUp
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ExportEcpayOrderLogs extends \Symfony\Component\Console\Command\Command
{
    const MAX_EXCEL_RECORDS = 1048576;

    const  FROM_DATE = 'from_date';
    const  TO_DATE = 'to_date';
    const  IGNORE_LIMIT = 'ignore_limit';
    const  ORDERS_PER_FILE = 'orders_per_file';
    const  ZIP_FILES = 'zip';
    /**
     * @var \Magento\Framework\File\Csv
     */
    private \Magento\Framework\File\Csv $csv;
    /**
     * @var \Magento\Framework\App\State
     */
    private \Magento\Framework\App\State $appState;

    private LoggerInterface $logger;
    private ResourceConnection $resourceConnection;
    private \Branch8\MarketPlaceOrderExport\Model\EcpayLogRecordProvider $ecpayLogRecordProvider;
    private \Magento\Framework\Stdlib\DateTime\Timezone $timezone;
    private GetTZOffsetTransitions $getTZOffsetTransitions;
    private StoreManager $storeManager;
    private EcpayLogWriter $ecpayLogWriter;
    /**
     * @var Emulation|mixed
     */
    private mixed $emulation;
    /**
     * @var ProgressBarFactory
     */
    private ProgressBarFactory $processBarFactory;
    /**
     * @var OutputInterface
     */
    private $output;
    private Registry $registry;

    /**
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Framework\File\Csv $csv
     * @param ResourceConnection $resourceConnection
     * @param \Branch8\MarketPlaceOrderExport\Model\EcpayLogRecordProvider $ecpayLogRecordProvider
     * @param GetTZOffsetTransitions $getTZOffsetTransitions
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     * @param StoreManager $storeManager
     * @param EcpayLogWriter $ecpayLogWriter
     * @param LoggerInterface $logger
     * @param ProgressBarFactory $progressBarFactory
     * @param Emulation|null $emulation
     */
    public function __construct(
        \Magento\Framework\App\State                                 $appState,
        \Magento\Framework\File\Csv                                  $csv,
        ResourceConnection                                           $resourceConnection,
        \Branch8\MarketPlaceOrderExport\Model\EcpayLogRecordProvider $ecpayLogRecordProvider,
        GetTZOffsetTransitions                                       $getTZOffsetTransitions,
        \Magento\Framework\Stdlib\DateTime\Timezone                  $timezone,
        StoreManager                                                 $storeManager,
        EcpayLogWriter                                               $ecpayLogWriter,
        LoggerInterface                                              $logger,
        ProgressBarFactory                                           $progressBarFactory,
        Registry                                                     $registry,
        Emulation                                                    $emulation = null,
    )
    {
        $this->timezone = $timezone;
        $this->getTZOffsetTransitions = $getTZOffsetTransitions;
        $this->resourceConnection = $resourceConnection;
        $this->csv = $csv;
        $this->storeManager = $storeManager;
        $this->ecpayLogRecordProvider = $ecpayLogRecordProvider;
        $this->appState = $appState;
        $this->logger = $logger;
        $this->ecpayLogWriter = $ecpayLogWriter;
        $this->registry = $registry;
        $this->processBarFactory = $progressBarFactory;
        $this->emulation = $emulation ?? ObjectManager::getInstance()->get(Emulation::class);
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('export:ecpay-order-logs');
        $this->setDescription('build ecpay excel order logs
         ex: php bin/magento export:ecpay-order-logs --from_date=\'2024-08-05 00:00:00\' --to_date=\'2024-08-05 23:59:00\' --zip=true --ignore_limit=false --orders_per_file=5000'
        );
        $this->addOption(
            self::FROM_DATE,
            null,
            InputOption::VALUE_REQUIRED,
            'input from_date'
        );
        $this->addOption(
            self::TO_DATE,
            null,
            InputOption::VALUE_REQUIRED,
            'input to_date'
        );

        $this->addOption(
            self::IGNORE_LIMIT,
            null,
            InputOption::VALUE_OPTIONAL,
            'Ignore Limit 10000'
        );
        $this->addOption(
            self::ZIP_FILES,
            null,
            InputOption::VALUE_OPTIONAL,
            'Zip files flag'
        );
        $this->addOption(
            self::ORDERS_PER_FILE,
            null,
            InputOption::VALUE_OPTIONAL,
            'Number of orders per file (default 5000)'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->setDecorated(true);
        $fromdate = $input->getOption(self::FROM_DATE);
        $todate = $input->getOption(self::TO_DATE);
        $ignoreLimit = filter_var($input->getOption(self::IGNORE_LIMIT), FILTER_VALIDATE_BOOLEAN);
        $zipFiles = filter_var($input->getOption(self::ZIP_FILES), FILTER_VALIDATE_BOOLEAN);
        $limit = $input->getOption(self::ORDERS_PER_FILE);
        $this->output = $output;
        $limit = $limit ? $limit : 5000;
        //$fromdate = '2025-08-01 00:00:00';
        //$todate = '2025-08-01 06:00:00';
        //$limit = 10;
       // $zipFiles = false;
        if (is_null($fromdate) || is_null($todate)) {
            throw new \Exception('Please set From Date and To Date');
        }
        $storeID = $this->storeManager->getStore()->getId();
        try {
            $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);
        } catch (\Exception $e) {
        }
        $this->emulation->startEnvironmentEmulation(1,
            Area::AREA_FRONTEND,
            true
        );
        if ($ignoreLimit) {
            $this->registry->register('ignore_limit', 1);
            $limit = 9999999;
        }
        $connection = $this->resourceConnection->getConnection();
        /**
         * 1.Get All Order then divide  into batch
         * 2.
         */
        $select = $this->getSelect($fromdate, $todate,
            [
                'totalOrders' => new \Zend_Db_Expr('COUNT(DISTINCT order_id)')
            ],
            null,
            null,
            'order_id'
        );
        $totalOrders = (int)$connection->fetchOne($select);
        $output->writeln("Total Orders: {$totalOrders}");
        $totalBatches = ceil($totalOrders / $limit);
        $processBar = $this->processBarFactory->create(
            [
                'output' => $output,
                'max' => $totalBatches
            ]
        );
        $processBar->setFormat(
            'Order/Batches  %current%/%max% %bar% %percent:3s%% %elapsed% %memory:6s%'
        );
        $processBar->start();
        $files = [];
        $uniqueId = uniqid();
        for ($page = 1; $page <= $totalBatches; $page++) {
            $this->ecpayLogWriter->reset();
            $offset = ($page - 1) * $limit;
            $fileNameUniqueId = sprintf('%s-orders-%s.xlsx', $uniqueId, $page);
            $this->ecpayLogWriter->setFileName($fileNameUniqueId)->writeHeader();
            $orderIds = $this->getOrders($fromdate, $todate, $limit, $offset);
            $logItems = $this->getLogItems($storeID, $fromdate, $todate, $orderIds);
            $allRecords = $this->ecpayLogRecordProvider
                ->getAllRecordOrderItems(array_unique($orderIds));
            $needToExcelRecord = $this->buildExcelRecords($logItems, $allRecords);
            $this->ecpayLogWriter->setRecords($needToExcelRecord)->writeRecords();
            $files[] = $this->ecpayLogWriter->save();
            $processBar->advance();
            gc_collect_cycles();
        }
        $processBar->finish();
        $output->write(PHP_EOL);
        if ($files) {
            if ($zipFiles) {
                $zipFileName = date('YmdHis') . '-orders.zip';
                $path = $this->zipFiles($zipFileName, $files);
                $output->writeln($path . PHP_EOL);
            } else {
                $output->writeln(join(PHP_EOL, $files) . PHP_EOL);
            }
        }else{
            $output->writeln('<info>No item found</info>');
        }
        $output->writeln('<info>Execution ends.</info>');
        $this->emulation->stopEnvironmentEmulation();
        return Command::SUCCESS;
    }

    /**
     * @param $zipFileName
     * @param $files
     * @return string
     */
    private function zipFiles($zipFileName, $files)
    {
        $base = BP . '/var/export';
        $command = 'cd ' . $base . ' && zip -j ' . $zipFileName . ' ' . join(' ', $files);
        system($command);
        foreach ($files as $file) {
            unlink($file);
        }
        return $base . '/' . $zipFileName;
    }

    /**
     * @param $logItems
     * @param $allRecords
     * @return array
     */
    private function buildExcelRecords($logItems, $allRecords)
    {
        $needToExcelRecord = [];

        foreach ($logItems as $logItem) {
            $orderId = $logItem['order_id'];
            $orderItems = [];
            $lastItem = null;
            if (isset($allRecords[$orderId])) {
                $orderItems = $allRecords[$orderId];
                $lastItem = end($orderItems);
            }
            if ($logItem['item_type'] === 'item') {
                if (isset($allRecords[$orderId][$logItem['invoice_order_item_id']])) {
                    $itemRecord = array_merge($allRecords[$orderId][$logItem['invoice_order_item_id']], $logItem);
                    $needToExcelRecord[] = $itemRecord;
                } else {
                    $this->output->writeln(__("ORDER + ORDER ITEM NOT MATCH ORDER-ID:%1-ITEM-ID:%2", $orderId, $logItem['invoice_order_item_id'])->render());
                }
            } elseif ($lastItem) {
                // clone last row of order then assign value it
                $newRecord = $lastItem;
                $itemRecord = array_merge($newRecord, $logItem);
                $needToExcelRecord[] = $itemRecord;
            }
        }
        return $needToExcelRecord;
    }

    /**
     * @param $storeID
     * @return array
     */
    private function getDateAdd($storeID)
    {
        $timezone = $this->timezone->getConfigTimezone(
            \Magento\Store\Model\ScopeInterface::SCOPE_STORES, $storeID);
        $dateAdd = $this->getTZOffsetTransitions->get($timezone);
        return $dateAdd;
    }

    /**
     * @param $storeID
     * @return array
     */
    private function getColumns($storeID)
    {
        $dateAdd = $this->getDateAdd($storeID);
        $columns = [
            'invoice_log_id' => 'main_table.hotai_order_invoice_logs_id',
            'invoice_number' => new \Zend_Db_Expr('COALESCE(main_table.invoice_number, \'no_value\')'),
            'order_id' => 'main_table.order_id',
            'invoice_status' => 'main_table.status',
            'invoice_order_item_id' => 'item.order_item_id',
            'invoice_order_item_name' => 'item.order_item_name',
            'item_include_tax' => 'item.include_tax',
            'price_incl_tax' => 'item.include_tax',
            'qty' => 'item.qty',
            'shipping_price_incl_tax' => 'item.include_tax',
            //'cost' => new \Zend_Db_Expr('""'),
            //'discount_amount' => new \Zend_Db_Expr('""'),
            'item_type' => 'item.type',
            'invoice_order_item_price' => 'item.include_tax',
            'product_name' => 'item.order_item_name',
            'is_reverse' => 'main_table.is_reverse',
            'db_invoice_created_date' => new \Zend_Db_Expr('DATE_FORMAT(main_table.created_at, "%Y-%m-%d")'),
            'ecpay_log_hotai_checkout_number' => 'main_table.hotai_checkout_number'
        ];
        if ($dateAdd) {
            // add offset follow locale store
            $offsetDate = $this->resourceConnection->getConnection()->getDateAddSql(
                'main_table.created_at',
                array_key_first($dateAdd),
                \Magento\Framework\DB\Adapter\AdapterInterface::INTERVAL_SECOND
            );
            $offsetItemDate = $this->resourceConnection->getConnection()->getDateAddSql(
                'item.created_at',
                array_key_first($dateAdd),
                \Magento\Framework\DB\Adapter\AdapterInterface::INTERVAL_SECOND
            );
            $columns['order_checkout_serial_number_date'] = $offsetDate;
            $columns['order_item_checkout_serial_number_date'] = $offsetItemDate;

        } else {
            $columns['order_checkout_serial_number_date'] = 'main_table.created_at';
            $columns['order_item_checkout_serial_number_date'] = 'item.created_at';
        }
        return $columns;
    }

    /**
     * @param $storeID
     * @param $fromdate
     * @param $todate
     * @param $limit
     * @param $offset
     * @return array
     */
    private function getLogItems($storeID, $fromdate, $todate, $orderIds)
    {
        $connection = $this->resourceConnection->getConnection();
        $columns = $this->getColumns($storeID);
        $select = $this->getSelect($fromdate, $todate, $columns);
        $select->where('main_table.order_id IN (?)', $orderIds);
        $logItems = $connection->fetchAll($select);
        return $logItems ?: [];
    }

    /**
     * @param $fromdate
     * @param $todate
     * @param $limit
     * @param $offset
     * @return array
     */
    private function getOrders($fromdate, $todate, $limit, $offset)
    {
        $columns = [
            'order_id' => new \Zend_Db_Expr('DISTINCT (main_table.order_id)'),
        ];
        $select = $this->getSelect($fromdate, $todate, $columns, $limit, $offset, 'order_id');
        $orders = $this->resourceConnection->getConnection()->fetchCol($select);
        return $orders ? $orders : [];
    }

    /**
     * @param $fromdate
     * @param $todate
     * @param $columns
     * @param $limit
     * @param $offset
     * @param $orderBy
     * @return \Magento\Framework\DB\Select
     */
    private function getSelect($fromdate, $todate, $columns = [], $limit = null, $offset = null, $orderBy = null)
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()->from(
            'ecpay_invoice_hotai_order_invoice_logs as main_table',
            $columns
        )->join('ecpay_invoice_hotai_order_item_invoice_logs as item',
            'main_table.hotai_order_invoice_logs_id = item.hotai_order_invoice_log_id',
            []
        )->where('main_table.created_at >= ?', $fromdate)
            ->where('main_table.created_at <= ?', $todate)->where('item.export_report = ? ', 1);
        if ($orderBy) {
            $select->order($orderBy);
        }
        if ($limit) {
            $select->limit($limit, $offset);
        }
        return $select;
    }
}
