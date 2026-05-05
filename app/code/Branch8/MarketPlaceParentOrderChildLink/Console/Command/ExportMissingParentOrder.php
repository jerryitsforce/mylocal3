<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderChildLink\Console\Command;

use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Model\Services\AssignDataForParentOrder;
use Branch8\MarketPlaceParentOrder\Model\Services\CopyAddressesFromSalesOrderToParentOrder;
use Branch8\MarketPlaceParentOrderChildLink\Model\Import\Importer;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Sales\Model\OrderRepository;
use phpseclib3\Math\BigInteger\Engines\PHP;
use Branch8\MarketPlaceParentOrderChildLink\Helper\Logger as LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderRepositoryInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderAddress;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Webkul\Mpsplitorder\Model\Mpsplitorder;

/**
 * Class ProductAttributesCleanUp
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ExportMissingParentOrder extends \Symfony\Component\Console\Command\Command
{
    const PAGE_SIZE = 'page_size';
    const AUTO_IMPORT = 'auto_import';
    private AssignDataForParentOrder $assignDataForParentOrder;
    private LoggerInterface $logger;
    private OrderRepository $orderRepository;
    private ParentOrderFactory $parentOrderFactory;
    private mixed $parentOrderRepository;
    private CopyAddressesFromSalesOrderToParentOrder $copyAddressesFromSalesOrder;
    private ParentOrderManagementInterface $parentOrderManagement;
    private \Magento\Framework\App\State $appState;
    private \Webkul\Mpsplitorder\Model\ResourceModel\Mpsplitorder\CollectionFactory $collectionFactory;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    private \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory;

    private \Magento\Framework\File\Csv $csv;
    private Importer $importer;

    /**
     * @param \Webkul\Mpsplitorder\Model\ResourceModel\Mpsplitorder\CollectionFactory $splitOrderCollectionFactory
     * @param ParentOrderRepositoryInterface $parentOrderRepository
     * @param OrderRepository $orderRepository
     * @param ParentOrderFactory $parentOrderFactory
     * @param AssignDataForParentOrder $assignDataForParentOrder
     * @param CopyAddressesFromSalesOrderToParentOrder $copyAddressesFromSalesOrderToParentOrder
     * @param ParentOrderManagementInterface $parentOrderManagement
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $collectionFactory
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Framework\File\Csv $csv
     * @param LoggerInterface $logger
     * @param Importer $importer
     */
    public function __construct(
        \Webkul\Mpsplitorder\Model\ResourceModel\Mpsplitorder\CollectionFactory $splitOrderCollectionFactory,
        ParentOrderRepositoryInterface                                          $parentOrderRepository,
        OrderRepository                                                         $orderRepository,
        ParentOrderFactory                                                      $parentOrderFactory,
        AssignDataForParentOrder                                                $assignDataForParentOrder,
        CopyAddressesFromSalesOrderToParentOrder                                $copyAddressesFromSalesOrderToParentOrder,
        ParentOrderManagementInterface                                          $parentOrderManagement,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory              $collectionFactory,
        \Magento\Framework\App\State                                            $appState,
        \Magento\Framework\File\Csv                                             $csv,
        LoggerInterface                                                         $logger,
        Importer                                                                $importer
    )
    {
        $this->collectionFactory = $splitOrderCollectionFactory;
        $this->assignDataForParentOrder = $assignDataForParentOrder;
        $this->logger = $logger;
        $this->orderCollectionFactory = $collectionFactory;
        $this->orderRepository = $orderRepository;
        $this->parentOrderFactory = $parentOrderFactory;
        $this->parentOrderRepository = $parentOrderRepository;
        $this->copyAddressesFromSalesOrder = $copyAddressesFromSalesOrderToParentOrder;
        $this->parentOrderManagement = $parentOrderManagement;
        $this->csv = $csv;
        $this->appState = $appState;
        $this->importer = $importer;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('marketplace:export-missing-parent-order');
        $this->addOption(
            (string)self::PAGE_SIZE,
            '',
            InputOption::VALUE_REQUIRED,
            'Add page size'
        );
        $this->addOption(
            (string)self::AUTO_IMPORT,
            '',
            InputOption::VALUE_NONE,
            'Auto Import'
        );
        $this->setDescription('Export and Build New Parent Order Increment Id For SubOrders');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->setDecorated(true);
        $pageSize = $input->getOption(self::PAGE_SIZE) ? (int)$input->getOption(self::PAGE_SIZE) : 5000;
        $autoImport = $input->getOption(self::AUTO_IMPORT);
        $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);
        $progress = new \Symfony\Component\Console\Helper\ProgressBar($output);
        $progress->setFormat('<comment>%message%</comment> %current%/%max% [%bar%] %percent:3s%% %elapsed%');
        $dir = BP . '/var/export/missing_parent_order';
        if (strpos($dir, BP) !== 0) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid path'));
        }
        $date = date('YmdHis');
        $zipFile = BP . '/var/export/missing_parent_order_' . $date . '.zip';
        if (strpos($zipFile, BP) !== 0) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid path'));
        }
        @unlink($zipFile);
        if (is_dir($dir)) {
            $this->deleteDirectory($dir);
        }
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        try {
            /**
             * @var $collection \Magento\Sales\Model\ResourceModel\Order\Collection
             */
            $collection = $this->orderCollectionFactory->create();
            $collection->getSelect()->joinLeft(
                'sales_parent_order_children',
                'main_table.entity_id =sales_parent_order_children.children_id')
                ->where('sales_parent_order_children.parent_id IS NULL')->order('created_at ASC');
            //   echo $collection->getSelect();die;
            $collection->setPageSize($pageSize);
            // ->setCurPage($pageNumber);
            $pages = $collection->getLastPageNumber();
            for ($pageNum = 1; $pageNum <= $pages; $pageNum++) {
                $collection->setCurPage($pageNum);
                $data = [];
                $existed = [];
                foreach ($collection as $order) {
                    if (in_array($order->getIncrementId(), $existed) || !$order->getIncrementId()) {
                        continue;
                    }
                    // ignore all orders not have prefix hotai_
                    $data[] = ['parent_order_number' => "", 'sub_order_number' => $order->getIncrementId()];
                    $existed[$order->getIncrementId()] = $order->getIncrementId();
                }
                //echo $collection->getSelect() . PHP_EOL;
                $collection->clear();
                $out = $dir . '/' . sprintf('missing_parent_order_%s_%05d.csv', $date, $pageNum);
                if ($data) {
                    array_unshift($data, array_keys($data[0]));
                    $this->csv->saveData($out, $data);
                    $output->writeln(__("Output File:%1", $out)->render());
                }
            }
            if ($autoImport) {
                $this->autoImport($dir, $output);
            }
            $zipFile = $this->scanAndZipFile($dir, $zipFile);
            $output->writeln(__("Output Zip File:%1", $zipFile)->render());
            return \Magento\Framework\Console\Cli::RETURN_SUCCESS;
        } catch (\Exception $exception) {
            $output->writeln("");
            $output->writeln("<error>{$exception->getMessage()}</error>");
            // we must have an exit code higher than zero to indicate something was wrong
            return \Magento\Framework\Console\Cli::RETURN_FAILURE;
        }
    }

    /**
     * @param $dir
     * @return void
     */
    private function autoImport($directory, OutputInterface $outPut)
    {
        $files = scandir($directory);
        $totalTime = 0;
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            $safeFile = basename($file);
            $filePath = $directory . DIRECTORY_SEPARATOR . $safeFile;
            $outPut->writeln('BEGIN PROCESS IMPORT FILE: ' . $filePath);
            $startTime = microtime(true);
            $this->importer->processImport($filePath);
            $endTime = microtime(true);
            $executionTime = round($endTime - $startTime, 2);
            $totalTime += $executionTime;
            $outPut->writeln('Time (s): ' . $this->convertMicroseconds($executionTime));
        }

        $outPut->writeln('Total time (s): ' . $this->convertMicroseconds($totalTime));
    }

    private function convertMicroseconds($seconds) {

        // Convert microseconds to seconds
        //$seconds = (int)($microseconds / 1000000);
        try{
            $hours = (int)($seconds / 3600);
            $minutes = (int)(($seconds % 3600) / 60);
            $seconds = (int)($seconds % 60);
        }catch (\Exception $e){
            return 0;
        }

        // Calculate hours, minutes, and remaining seconds

        return sprintf("%02d:%02d:%02d", $hours, $minutes, round($seconds));
    }
    /**
     * @param $directory
     * @param $zipFile
     * @return mixed
     * @throws \Exception
     */
    private function scanAndZipFile($directory, $zipFile)
    {
        if (strpos($directory, BP) !== 0 || strpos($zipFile, BP) !== 0) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid path'));
        }
        $zip = new \ZipArchive();
        if ($zip->open($zipFile, \ZipArchive::CREATE) === TRUE) {
            $files = scandir($directory);
            foreach ($files as $file) {
                if ($file == '.' || $file == '..') {
                    continue;
                }
                $safeFile = basename($file);
                $filePath = $directory . DIRECTORY_SEPARATOR . $safeFile;
                if (is_file($filePath)) {
                    $zip->addFile($filePath, $safeFile); // Add file with the original file name
                }
            }
            $zip->close();
            return $zipFile;
        } else {
            throw new \Exception("Can't zip dir:" . $directory);
        }
    }

    /**
     * @param $dir
     * @return bool
     */
    private function deleteDirectory($dir)
    {
        if (strpos($dir, BP) !== 0) {
            return false;
        }
        if (!file_exists($dir)) {
            return true;
        }

        if (!is_dir($dir)) {
            return unlink($dir);
        }

        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }

            if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . basename($item))) {
                return false;
            }

        }
        return rmdir($dir);
    }
}
