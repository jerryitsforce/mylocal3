<?php

namespace Branch8\DataFeedManager\Rewrite\Model\ResourceModel\Product;

use Magento\Catalog\Model\Category;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\InventoryCatalog\Model\ResourceModel\AddStockDataToCollection;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;
use Magento\InventorySales\Model\StockByWebsiteIdResolver;
use Webkul\OptionsWithStockAndImages\Model\VariationsFactory;

class Collection extends \Wyomind\DataFeedManager\Model\ResourceModel\Product\Collection
{

    private mixed $addStockHandle = null;

    private $stockResolver = null;

    private mixed $getProductSalebleQty = null;

    private VariationsFactory $variationsFactory;

    /**
     * @return StockByWebsiteIdResolver
     */
    private function stockResolver()
    {
        if ($this->stockResolver === null) {
            $this->stockResolver = ObjectManager::getInstance()->get(StockByWebsiteIdResolver::class);
        }
        return $this->stockResolver;
    }

    /**
     * @return \Magento\InventorySalesApi\Api\GetProductSalableQtyInterface
     */
    private function getProductSalebleQty()
    {
        if ($this->getProductSalebleQty === null) {
            $this->getProductSalebleQty = ObjectManager::getInstance()->get(\Magento\InventorySalesApi\Api\GetProductSalableQtyInterface::class);
        }
        return $this->getProductSalebleQty;
    }

    /**
     * @inheritDoc
     */
    public function getMainRequest($storeId, $websiteId, $notLike, $concat, $manageStock, $listOfAttributes, $categoriesFilterList, $condition, $params, $includeDisabled)
    {
        $storeRootId = $this->_storeManager->getStore($params['store_id'])->getRootCategoryId();
        $categoryRootId = Category::TREE_ROOT_ID;
        $connection = $this->_resource;
        $tableCpsl = $connection->getTableName('catalog_product_super_link');
        $tableCpe = $connection->getTableName("catalog_product_entity");
        $tableCce = $connection->getTableName("catalog_category_entity");
        $tableCsi = $connection->getTableName('cataloginventory_stock_item');
        $tableCur = $connection->getTableName('url_rewrite');
        $tableCcpi = $connection->getTableName('catalog_category_product');
        $tableCurpc = $connection->getTableName('catalog_url_rewrite_product_category');
        $tableCpip = $connection->getTableName('catalog_product_index_price');
        $this->addStoreFilter($storeId);
        if (!$includeDisabled) {
            $this->addAttributeToFilter('status', '1');
        }
        if (!in_array("*", explode(',', (string)$params['type_ids']))) {
            $this->addAttributeToFilter('type_id', ['in' => explode(',', (string)$params['type_ids'])]);
        }
        if (!in_array("*", explode(',', (string)$params['visibilities']))) {
            $this->addAttributeToFilter('visibility', ['in' => explode(',', (string)$params['visibilities'])]);
        }
        if (!in_array("*", explode(",", (string)$params['attribute_sets']))) {
            $this->addAttributeToFilter('attribute_set_id', ['in' => explode(',', (string)$params['attribute_sets'])]);
        }

        // Custom here - START
        $hideProductIds = $this->getHideProductIds();
        if (!empty($hideProductIds)) {
            $this->addIdFilter($hideProductIds, true);
        }

        $hideCategoryIds = $this->getHideCategoryIds();
        if (!empty($hideCategoryIds)) {
            $this->addCategoriesFilter(['nin' => $hideCategoryIds]);
        }
        // Custom here - END

        $joinType = count($listOfAttributes) <= self::MAX_ATTRIBUTE;
        $this->addAttributeToSelect($listOfAttributes, $joinType);
        $where = '';
        $a = 0;
        $tempFilter = [];
        if ($manageStock != 1 && $manageStock != 0) {
            throw new \Exception(__('Invalid data'));
        } else {
            $manageStock = htmlspecialchars($manageStock);
        }

        $oosProductIds = false;
        foreach (json_decode((string)$params['attributes']) as $attributeFilter) {
            if ($attributeFilter->checked) {
                if ($attributeFilter->condition == 'in' || $attributeFilter->condition == 'nin') {
                    if ($attributeFilter->code == 'qty' || $attributeFilter->code == 'is_in_stock') {
                        if (!is_array($attributeFilter->value)) {
                            $array = explode(',', (string)$attributeFilter->value);
                        } else {
                            $array = $attributeFilter;
                        }
                        $attributeFilter->value = "'" . implode("','", $array) . "'";
                    } else {
                        if (!is_array($attributeFilter->value)) {
                            $attributeFilter->value = explode(',', (string)$attributeFilter->value);
                        }
                    }
                }
                if ($attributeFilter->condition == 'like' || $attributeFilter->condition == 'nlike') {
                    if (!is_array($attributeFilter->value)) {
                        $attributeFilter->value = explode(',', (string)$attributeFilter->value);
                    }
                }
                if (!isset($attributeFilter->statement)) {
                    $attributeFilter->statement = "";
                }
                switch ($attributeFilter->code) {
                    case 'qty':
                        if ($a > 0) {
                            $where .= ' ' . $attributeFilter->statement . ' ';
                        }
                        $where .= ' qty ' . sprintf($condition[$attributeFilter->condition], $attributeFilter->value);
                        $a++;
                        break;
                    case 'is_in_stock':
                        if ($a > 0) {
                            $where .= ' ' . $attributeFilter->statement . ' ';
                        }
                        if ($attributeFilter->value == '1' && $oosProductIds === false) {
                            $oosProductIds = $this->getOosProductIdsWithCustomOptions();
                            if (!empty($oosProductIds)) {
                                $this->addIdFilter($oosProductIds, true);
                            }
                        }
                        $where .= "(IF(";
                        // use_config_manage_stock=1 && default_manage_stock=0
                        $where .= "(use_config_manage_stock=1 AND {$manageStock}=0)";
                        // use_config_manage_stock=0 && manage_stock=0
                        $where .= " OR ";
                        $where .= "(use_config_manage_stock=0 AND manage_stock=0)";
                        // use_config_manage_stock=1 && default_manage_stock=1 && in_stock=1
                        $where .= " OR ";
                        $where .= "(use_config_manage_stock=1 AND {$manageStock}=1 AND is_in_stock=1 )";
                        // use_config_manage_stock=0 && manage_stock=1 && in_stock=1
                        $where .= " OR ";
                        $where .= "(use_config_manage_stock=0 AND manage_stock=1 AND is_in_stock=1 )";
                        $where .= ",'1','0')" . sprintf($condition[$attributeFilter->condition], $attributeFilter->value) . ")";
                        $a++;
                        break;
                    default:
                        if ($attributeFilter->statement == 'AND') {
                            if (count($tempFilter)) {
                                $this->addFieldToFilter($tempFilter);
                            }
                            $tempFilter = [];
                        }
                        // Custom here - START
                        if ($attributeFilter->condition == 'in') {
                            $finset = false;
                            $findInSet = [];
                            foreach ($attributeFilter->value as $v) {
                                if (!is_numeric($v)) {
                                    $finset = true;
                                    break;
                                }
                            }
                            if ($finset) {
                                foreach ($attributeFilter->value as $v) {
                                    $findInSet[] = [
                                        'attribute' => $attributeFilter->code,
                                        'finset' => $v
                                    ];
                                }
                                $tempFilter = array_merge($tempFilter, $findInSet);
                            } else {
                                $tempFilter[] = [
                                    'attribute' => $attributeFilter->code,
                                    'in' => $attributeFilter->value
                                ];
                            }
                        } elseif ($attributeFilter->condition === 'nin') {
                            $tempFilter[] = [
                                'attribute' => $attributeFilter->code,
                                'nin' => $attributeFilter->value
                            ];
                        } elseif (in_array($attributeFilter->condition, ['like', 'nlike'])) {
                            if ($attributeFilter->condition === 'nlike' && is_array($attributeFilter->value)) {
                                foreach ($attributeFilter->value as $v) {
                                    $this->addFieldToFilter($attributeFilter->code, ['nlike' => "%$v%"]);
                                }
                            } else {
                                if (is_array($attributeFilter->value)) {
                                    foreach ($attributeFilter->value as $v) {
                                        $tempFilter[] = [
                                            'attribute' => $attributeFilter->code,
                                            $attributeFilter->condition => "%$v%"
                                        ];
                                    }
                                } else {
                                    $tempFilter[] = [
                                        'attribute' => $attributeFilter->code,
                                        $attributeFilter->condition => "%$attributeFilter->value%"
                                    ];
                                }
                            }
                        } else {
                            $tempFilter[] = [
                                'attribute' => $attributeFilter->code,
                                $attributeFilter->condition => $attributeFilter->value
                            ];
                        }
                        // Custom here - END
                        break;
                }
            }
        }
        if (count($tempFilter)) {
            $this->addFieldToFilter($tempFilter);
        }
        $this->getSelect()->joinLeft(['stock' => $tableCsi], "stock.product_id=e.entity_id", ["qty" => "qty", "is_in_stock" => "is_in_stock", "manage_stock" => "manage_stock", "use_config_manage_stock" => "use_config_manage_stock", "backorders" => "backorders", "use_config_backorders" => "use_config_backorders"]);
        $this->getSelect()->joinLeft(['url' => $tableCur], "url.entity_id=e.entity_id " . $notLike . " AND url.entity_type ='product' AND url.store_id=" . $storeId, ["request_path" => $concat . "(DISTINCT request_path)"]);
        $this->getSelect()->joinLeft(["curpc" => $tableCurpc], "url.url_rewrite_id=curpc.url_rewrite_id and e.entity_id = curpc.product_id");
        if ($categoriesFilterList[0] != "*") {
            $v = 0;
            $filter = null;
            foreach ($categoriesFilterList as $categoriesFilter) {
                if ($v > 0) {
                    $filter .= ',';
                }
                $explode = explode("/", (string)$categoriesFilter);
                $filter .= array_pop($explode);
                $v++;
            }
            $in = $params['category_filter'] ? 'IN' : 'NOT IN';
            $ct = '';
            switch ($params['category_type']) {
                case self::CATEGORIES_FILTER_PRODUCT:
                    $ct = 'categories.product_id=e.entity_id';
                    break;
                case self::CATEGORIES_FILTER_PRODUCT_AND_PARENT:
                    $this->getSelect()->joinLeft(['cpsl' => $tableCpsl], "cpsl.product_id=e.entity_id", ['parent_id' => 'parent_id']);
                    $this->getSelect()->joinLeft(['cpslcpe' => $tableCpe], "cpsl.parent_id=cpslcpe." . $this->_rowId . "", []);
                    $ct = "(categories.product_id=e.entity_id OR categories.product_id=cpslcpe.entity_id)";
                    break;
                case self::CATEGORIES_FILTER_PARENT:
                    $this->getSelect()->joinLeft(['cpsl' => $tableCpsl], "cpsl.product_id=e.entity_id", ['parent_id' => 'parent_id']);
                    $this->getSelect()->joinLeft(['cpslcpe' => $tableCpe], "cpsl.parent_id=cpslcpe." . $this->_rowId . "", []);
                    $ct = "categories.product_id=cpslcpe.entity_id ";
                    break;
            }
            $filter = " AND categories.category_id " . $in . "(" . $filter . ") ";
            $this->getSelect()->joinInner(['categories' => $tableCcpi], $ct . $filter, ["categories_ids" => "GROUP_CONCAT( DISTINCT categories.category_id)"]);
            $this->getSelect()->joinInner(['cce' => $tableCce], "categories.category_id=cce.entity_id AND cce.path LIKE '" . $categoryRootId . "/" . $storeRootId . "/%'", []);
        } else {
            $this->getSelect()->joinLeft(['categories' => $tableCcpi], "categories.product_id=e.entity_id", ["categories_ids" => "GROUP_CONCAT( DISTINCT categories.category_id)"]);
            $this->getSelect()->joinLeft(['cce' => $tableCce], "categories.category_id=cce.entity_id AND cce.path LIKE '" . $categoryRootId . "/" . $storeRootId . "/%'", []);
        }
        $this->getSelect()->joinLeft(['price_index' => $tableCpip, ['websiteId' => 'website_id']], "price_index.entity_id=e.entity_id AND customer_group_id=0 AND  price_index.website_id=" . $websiteId, ['min_price' => 'min_price', 'max_price' => 'max_price', 'final_price' => 'final_price', 'base_price' => 'price']);
        if (!empty($where)) {
            $this->getSelect()->where($where);
        }
        $this->getSelect()->group('e.entity_id');
        $this->addSaleableQuantity($this);
        $this->addLog('getMainRequest', ['query' => $this->getSelect()->__toString()]);
        return $this;
    }

    /**
     * @return \Magento\InventoryCatalog\Model\ResourceModel\AddStockDataToCollection
     */
    private function getAddStockDataHandle()
    {
        if (!$this->addStockHandle) {
            $this->addStockHandle = ObjectManager::getInstance()->get(AddStockDataToCollection::class);
        }
        return $this->addStockHandle;
    }

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $collection
     * @return \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    private function addSaleableQuantity(\Magento\Catalog\Model\ResourceModel\Product\Collection $collection)
    {
        $this->getAddStockDataHandle()->execute($collection, false, 1);
        return $collection;
    }

    /**
     * Returns hide category IDs.
     *
     * @return array
     */
    private function getHideProductIds(): array
    {
        $connection = $this->_resource->getConnection();
        $tableNameRule = $this->_resource->getTableName('amasty_groupcat_rule');
        $tableNameProduct = $this->_resource->getTableName('amasty_groupcat_rule_product');

        $select = $connection->select()
            ->distinct()
            ->from(['agr' => $tableNameRule], [])
            ->where('agr.is_active = 1 AND agr.hide_product = 1')
            ->join(
                ['arp' => $tableNameProduct],
                'arp.rule_id = agr.rule_id',
                ['product_id']
            );

        $this->addLog('getHideProductIds', [
                'query' => $select->__toString(),
                'result' => $connection->fetchCol($select)
            ]
        );

        return (array)$connection->fetchCol($select);
    }

    /**
     * Returns hide category IDs.
     *
     * @return array
     */
    private function getHideCategoryIds(): array
    {
        $connection = $this->_resource->getConnection();
        $tableNameRule = $this->_resource->getTableName('amasty_groupcat_rule');
        $tableNameCategory = $this->_resource->getTableName('amasty_groupcat_rule_category');

        $select = $connection->select()
            ->distinct()
            ->from(['agr' => $tableNameRule], [])
            ->where('agr.is_active = 1 AND agr.hide_category = 1')
            ->join(
                ['arc' => $tableNameCategory],
                'arc.rule_id = agr.rule_id',
                ['category_id']
            );

        $this->addLog('getHideCategoryIds', [
                'query' => $select->__toString(),
                'result' => $connection->fetchCol($select)
            ]
        );

        return (array)$connection->fetchCol($select);
    }

    /**
     * Get product IDs with custom options that are fully out of stock.
     *
     * @return array
     */
    private function getOosProductIdsWithCustomOptions(): array
    {
        $connection = $this->_resource->getConnection();
        $wkTable = $this->_resource->getTableName('wk_osi_variations');
        $productTable = $this->_resource->getTableName('catalog_product_entity');

        $select = $connection->select()
            ->from(['wv' => $wkTable], [])
            ->join(
                ['cpe' => $productTable],
                'wv.product_id = cpe.row_id',
                ['product_id' => 'cpe.entity_id']
            )
            ->group('cpe.entity_id')
            ->having('SUM(wv.stock) <= ?', 0);

        return (array)$connection->fetchCol($select);
    }

    /**
     * Temporary add saleable_qty to item,
     * @TODO Need to build index and join to collection to increase performance
     * @param DataObject $item
     * @return $this|Collection
     * @throws \Exception
     */
    public function addItem(DataObject $item)
    {
        $ignores = [
            'configurable',
            'bundle',
            'grouped',
        ];
        $itemId = $this->_getItemId($item);
        if (in_array($item->getTypeId(), $ignores)) {
            return parent::addItem($item);
        }
        /**
         * @TODO  Build Index Data which fields need for export
         */
        $item->setData('saleable_qty', 0);
        try {
            $saleableQty =$this->getSaleableQty($item);
            $item->setData('saleable_qty', (int)$saleableQty);
        } catch (\Throwable $exception) {
            $this->_logger->error($exception->getMessage());
            $this->_logger->info('Item ID:' . $item->getSku());
            $this->_logger->info($exception->getTraceAsString());
        }
        if ($itemId !== null) {
            if (isset($this->_items[$itemId])) {
                //phpcs:ignore Magento2.Exceptions.DirectThrow
                throw new \Exception(
                    'Item (' . get_class($item) . ') with the same ID "' . $item->getId() . '" already exists.'
                );
            }
            $this->_items[$itemId] = $item;
        } else {
            $this->_addItem($item);
        }
        return $this;
    }

    /**
     * @param DataObject $item
     * @return int
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getSaleableQty(DataObject $item)
    {
        $total = 0;
        $variationStock = $this->getVariationStock($item->getRowId());
        if (!is_null($variationStock)) {
            $total = $variationStock;
        } else {
            if (!$item->getSku()) {
                return 0;
            }
            $websiteId = $item->getWebsiteIds() ? $item->getWebsiteIds()[0] : $this->_storeManager->getDefaultStoreView()->getWebsiteId();
            $stock = $this->stockResolver()->execute($websiteId);
            $total = $this->getProductSalebleQty()->execute($item->getSku(),
                $stock->getStockId() ? (int)$stock->getStockId() : 1
            );
        }
        return $total ? (int)$total : 0;
    }

    /**
     * @param $row_id
     * @return int|null
     */
    private function getVariationStock($row_id)
    {
        $total = null;
        $collection = ObjectManager::getInstance()->get(VariationsFactory::class)->create()->getCollection()
            ->addFieldToFilter("product_id", $row_id);
        if ($collection->getSize()) {
            $total = 0;
            foreach ($collection as $variation) {
                $total += $variation->getStock();
            }
        }
        return $total;
    }

    /**
     * Write log to check.
     *
     * @param string $message
     * @param array $context
     *
     * @return void
     */
    private function addLog(string $message, array $context = []): void
    {
        $logFile = BP . '/var/log/branch8_data_feed_manager.log';
        $logger = new \Monolog\Logger('branch8_data_feed_manager');
        $logger->pushHandler(new \Monolog\Handler\StreamHandler($logFile, \Monolog\Logger::INFO));
        $logger->info($message, $context);
    }
}
