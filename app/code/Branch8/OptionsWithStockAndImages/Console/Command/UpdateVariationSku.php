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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\FilterBuilder;
use Magento\Sales\Api\Data\OrderItemInterface;
use Webkul\OptionsWithStockAndImages\Helper\Data as Helper;
use Magento\Framework\App\ResourceConnection;
use Webkul\OptionsWithStockAndImages\Logger\Logger;

class UpdateVariationSku extends Command
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
        SearchCriteriaBuilder        $searchCriteriaBuilder,
        FilterBuilder                $filterBuilder,
        Helper                       $helper,
        Logger                       $logger,
        ProductRepositoryInterface   $productRepository,
        ResourceConnection           $resourceConnection
    )
    {
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
        InputInterface  $input,
        OutputInterface $output
    ): int
    {
        try {
            $output->writeln("<info>Starting update variation sku for order items...</info>");
            $itemId = $input->getOption('itemId') ?? null;
            $this->logger->info('Starting update variation sku for order items...');
            // Example: Get all order items.
            $filterOptionSku = $this->filterBuilder
                ->setField('option_sku')
                ->setConditionType('null')
                ->create();
            $filterVariationSku = $this->filterBuilder
                ->setField('variation_sku')
                ->setConditionType('null')
                ->create();
            if ($itemId) {
                $this->searchCriteriaBuilder->addFilter('item_id', $itemId);
            } else {
                $filters = [$filterOptionSku, $filterVariationSku];
                $this->searchCriteriaBuilder->addFilters($filters);
            }
            $searchCriteria = $this->searchCriteriaBuilder->create();
            $orderItems = $this->orderItemRepository->getList($searchCriteria);
            foreach ($orderItems as $item) {
                $comb = "";
                $option_type_id = [];
                $product = $item->getProduct();
                if (!$product) {
                    continue;
                }
                $productRowId = $product->getRowId();
                $optionData = [];
                foreach ($product->getOptions() as $option) {
                    $optType = $option->getType();
                    if ($optType == "drop-down" || $optType == "drop_down" || $optType == "radio") {
                        $optionId = $option->getId();
                        $optionData[$optionId] = [];
                        foreach ($option->getValues() as $value) {
                            $valueId = $value->getId();
                            $optionData[$optionId][$valueId] = $value->getDefaultTitle();
                        }
                    }
                }
                if (!empty($optionData)) {
                    $options = $item->getProductOptions();
                    if (isset($options['options']) && !empty($options['options'])) {
                        foreach ($options['options'] as $option) {
                            if (!isset($optionData[$option['option_id']])) {
                                continue;
                            }
                            $optDataArr = $optionData[$option['option_id']];
                            if (isset($optDataArr) && isset($optDataArr[$option['option_value']])) {
                                $comb .= $optDataArr[$option['option_value']] . "_";
                                $option_type_id[] = $option['option_value'];
                            }
                        }

                        $comb = trim($comb, "_");
                        $variation = $this->helper->getCombData($productRowId, $comb);
                        $saveItem = false;
                        if ($variation->getId()) {
                            $saveItem = true;
                            if (!empty($variation->getSku())) {
                                $item->setVariationSku($variation->getSku());
                            }
                            $item->setSpecTitle($variation->getComb());
                        }
                        if (count($option_type_id)) {
                            $customOptionSkus = $this->getCustomOptionSkus($option_type_id);
                            $customOptionSkus = array_filter($customOptionSkus);
                            $saveItem = true;
                            if (count($customOptionSkus)) {
                                $item->setOptionSku(implode('-', $customOptionSkus));
                            } else {
                                $item->setOptionSku(null);
                            }
                        }
                        if ($saveItem) {
                            $item->save();
                            $this->logger->info('Update for order item id: ' . $item->getItemId());
                        }
                    }
                }
            }

            $output->writeln("<info>Done update variation sku for order items!</info>");
            $this->logger->info('Done update variation sku for order items!');
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
        $this->setName("sales:updatevariationsku");
        $this->setDescription("update variation sku for order items");
        $this->addOption('itemId', null, InputOption::VALUE_REQUIRED,
            'input item_id like this: --item_id=123');
        parent::configure();
    }

    private function getCustomOptionSkus($option_type_id)
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('catalog_product_option_type_value');
        $select = $connection->select()
            ->from($table, ['sku'])
            ->where('option_type_id IN (?)', $option_type_id)
            ->where('sku IS NOT NULL');
        return $connection->fetchCol($select);
    }
}
