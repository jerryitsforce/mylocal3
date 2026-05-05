<?php
declare(strict_types=1);

namespace Branch8\Marketplace\Console\Command;

use Branch8\Marketplace\Model\Actions\BuildMarketPlaceSaleListRecord;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Ddl\Table;
use Magento\Store\Model\App\Emulation;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Branch8\Marketplace\Service\MarketplaceLogger;

class FixMissingRecordMarketPlaceSaleList extends \Symfony\Component\Console\Command\Command
{
    const  ORDER_ID = 'ids';
    const  DRY_RUN = 'dry_run';
    /**
     * @var Emulation|mixed
     */
    private mixed $emulation;
    /**
     * @var \Magento\Framework\App\State
     */
    private \Magento\Framework\App\State $appState;
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    private MarketplaceLogger $marketplaceLogger;
    /**
     * @var BuildMarketPlaceSaleListRecord
     */
    private BuildMarketPlaceSaleListRecord $buildMarketPlaceSaleListRecord;
    private ResourceConnection $resourceConnection;
    private \Webkul\Marketplace\Model\ResourceModel\Orders\CollectionFactory $collectionFactory;

    /**
     * @param \Magento\Framework\App\State $appState
     * @param LoggerInterface $logger
     * @param BuildMarketPlaceSaleListRecord $buildMarketPlaceSaleListRecord
     * @param ResourceConnection $resourceConnection
     * @param MarketplaceLogger $marketplaceLogger
     * @param Emulation|null $emulation
     */
    public function __construct(
        \Magento\Framework\App\State                                     $appState,
        LoggerInterface                                                  $logger,
        BuildMarketPlaceSaleListRecord                                   $buildMarketPlaceSaleListRecord,
        ResourceConnection                                               $resourceConnection,
        \Webkul\Marketplace\Model\ResourceModel\Orders\CollectionFactory $ordersCollectionFactory,
        MarketplaceLogger                                                $marketplaceLogger,
        Emulation                                                        $emulation = null,
    )
    {
        $this->collectionFactory = $ordersCollectionFactory;
        $this->resourceConnection = $resourceConnection;
        $this->appState = $appState;
        $this->logger = $logger;
        $this->buildMarketPlaceSaleListRecord = $buildMarketPlaceSaleListRecord;
        $this->emulation = $emulation ?? ObjectManager::getInstance()->get(Emulation::class);
        $this->marketplaceLogger = $marketplaceLogger;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('marketplace:rebuild-marketplace-saleslist');
        $this->setDescription('Fix Missing Record On Table marketplace_saleslist');
        $this->addOption(
            self::ORDER_ID,
            null,
            InputOption::VALUE_REQUIRED,
            'Increment ID,separate by comma,"empty" mean for all missing order'
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->setDecorated(true);
        $ids = $input->getOption(self::ORDER_ID);
        $allCase = false;
        if ($ids === 'all') {
            $allCase = true;
        } else {
            $ids = explode(',', trim($input->getOption(self::ORDER_ID) ?: ''));
        }
        try {
            $this->appState->setAreaCode(
                \Magento\Framework\App\Area::AREA_GLOBAL
            );
        } catch (\Exception $e) {
            $this->marketplaceLogger->logException('FixMissingRecordMarketPlaceSaleList', $e);
        }
        if ($allCase) {
            $this->processAll($output);
        } else {
            $this->process($ids);
        }
        return Command::SUCCESS;
    }

    /**
     * @param $ids
     * @return void
     */
    private function process($ids)
    {
        $this->buildMarketPlaceSaleListRecord->execute($ids);
    }

    /**
     * @return void
     */
    private function processAll(OutputInterface $output)
    {
        /**
         * @var $connection \Magento\Framework\DB\Adapter\AdapterInterface
         */
        $collection = $this->collectionFactory->create();
        $pageSize = 100;
        $collection->getSelect()->joinLeft(
            'marketplace_saleslist',
            'main_table.order_id = marketplace_saleslist.order_id'
        )->where('marketplace_saleslist.order_id IS NUll')->reset('columns')
            ->columns(['order_id' => 'main_table.order_id'])
            ->order('main_table.entity_id ASC');
        $pageCount = $collection->setPageSize($pageSize)->getLastPageNumber();
        for ($page = 1; $page <= $pageCount; $page++) {
            $ids = [];
            $collection->setCurPage($page);
            foreach ($collection as $item) {
                $ids[] = $item->getOrderId();
            }
            $this->buildMarketPlaceSaleListRecord->execute($ids);
            $collection->clear();
        }
    }
}
