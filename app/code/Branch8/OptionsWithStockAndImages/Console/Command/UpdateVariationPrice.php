<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\OptionsWithStockAndImages\Console\Command;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Sales\Api\Data\OrderItemInterface;
use Webkul\OptionsWithStockAndImages\Helper\Data as Helper;
use Magento\Framework\App\ResourceConnection;
use Webkul\OptionsWithStockAndImages\Logger\Logger;

class UpdateVariationPrice extends Command
{
    /**
     * @var OrderItemRepositoryInterface
     */
    protected $orderItemRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var FilterBuilder
     */
    protected $filterBuilder;
    /**
     * @var Helper
     */
    private Helper $helper;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;
    
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder $filterBuilder
     * @param Helper $helper
     * @param Logger $logger
     * @param ProductRepositoryInterface $productRepository
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        OrderItemRepositoryInterface $orderItemRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        Helper $helper,
        Logger $logger,
        ProductRepositoryInterface $productRepository,
        ResourceConnection $resourceConnection
    ) {
        $this->filterBuilder = $filterBuilder;
        $this->orderItemRepository = $orderItemRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->helper = $helper;
        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->resourceConnection = $resourceConnection;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        try {
            $output->writeln("<info>Starting update variation price...</info>");
            $this->logger->info('Starting update variation price...');
            
            $variationProductRowIds = $this->getVariationProductRowIds();
            if(empty($variationProductRowIds)){
                $output->writeln("<info>No variation products found.</info>");
                $this->logger->info('No variation products found.');
                return Command::SUCCESS;
            }

            $productCollection = $this->getProductCollection($variationProductRowIds);
            if($productCollection->getSize() == 0){
                $output->writeln("<info>No products found for the given row IDs.</info>");
                $this->logger->info('No products found for the given row IDs.');
                return Command::SUCCESS;
            } else {
                foreach ($productCollection as $product) {
                    $finalPrice = $product->getFinalPrice();
                    $productId = $product->getData('row_id');
                    $connection = $this->resourceConnection->getConnection();
                    $tableName = $this->resourceConnection->getTableName('wk_osi_variations');

                    $bind = [
                        'price' => $finalPrice,
                        'follow_simple_sku_price_setting' => 1
                    ];
                    $where = ['product_id = ?' => $productId];

                    try {
                        $connection->update($tableName, $bind, $where);
                        $output->writeln("<info>Updated price for product ID: $productId to $finalPrice</info>");
                        $this->logger->info("Updated price for product ID: $productId to $finalPrice");
                    } catch (Exception $e) {
                        $output->writeln("<error>Error updating price for product ID: $productId - " . $e->getMessage() . "</error>");
                        $this->logger->error("Error updating price for product ID: $productId - " . $e->getMessage());
                    }
                }
            }

            $output->writeln("<info>Done update variation price for order items!</info>");
            $this->logger->info('Done update variation price for order items!');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            $this->logger->error($e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->setName("sales:updatevariationprice");
        $this->setDescription("update variation price");

        parent::configure();
    }

    private function getProductCollection(array $rowIds)
    {
        /** @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory */
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $collectionFactory = $objectManager->get(\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory::class);

        $collection = $collectionFactory->create();
        $collection->addAttributeToSelect(['price', 'special_price', 'sku', 'name']);
        $collection->addFieldToFilter('row_id', ['in' => $rowIds]);

        return $collection;
    }

    private function getVariationProductRowIds(){
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('wk_osi_variations');

        $select = $connection->select()->distinct()
            ->from($table, ['product_id'])->where('price IS NULL OR price = 0');

        return $connection->fetchCol($select);
    }
}
