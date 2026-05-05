<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model\ResourceModel\SellerProductManagement\Grid;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\View\Element\UiComponent\DataProvider\Document;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Collection extends \Webkul\Marketplace\Model\ResourceModel\Product\Grid\Collection
{
    /**
     * @var ProductAttributeRepositoryInterface
     */
    private ProductAttributeRepositoryInterface $productAttributeRepository;

    protected $_filtered = [];

    /**
     * Collection constructor.
     *
     * @param EntityFactoryInterface $entityFactory
     * @param LoggerInterface $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param ManagerInterface $eventManager
     * @param StoreManagerInterface $storeManager
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     * @param string $mainTable
     * @param mixed $eventPrefix
     * @param string $eventObject
     * @param string $resourceModel
     * @param string $model
     * @param AdapterInterface|null $connection
     * @param AbstractDb|null $resource
     */
    public function __construct(
        EntityFactoryInterface              $entityFactory,
        LoggerInterface                     $logger,
        FetchStrategyInterface              $fetchStrategy,
        ManagerInterface                    $eventManager,
        StoreManagerInterface               $storeManager,
        ProductAttributeRepositoryInterface $productAttributeRepository,
        string                              $mainTable,
        mixed                               $eventPrefix,
        string                              $eventObject,
        string                              $resourceModel,
        string                              $model = Document::class,
        AdapterInterface                    $connection = null,
        AbstractDb                          $resource = null
    ) {
        parent::__construct(
            $entityFactory,
            $logger,
            $fetchStrategy,
            $eventManager,
            $storeManager,
            $mainTable,
            $eventPrefix,
            $eventObject,
            $resourceModel,
            $model,
            $connection,
            $resource
        );
        $this->productAttributeRepository = $productAttributeRepository;
    }

    /**
     * @inheritdoc
     */
    protected function _initSelect(): void
    {
        $this->addFilterToMap('product_name', 'at_name.value');
        $this->addFilterToMap('mage_store_id', 'at_name.store_id');
        $this->addFilterToMap('product_status', 'at_status.value');
        $this->addFilterToMap('created_at', 'main_table.created_at');
        $this->addFilterToMap('updated_at', 'main_table.updated_at');
        $this->addFilterToMap('cost', 'at_cost.value');
        $this->addFilterToMap('product_price', 'at_price.value');
        $this->addFilterToMap('name', 'at_customer.name');
        $this->addFilterToMap('admin_user_updated', 'at_admin_user_updated.value');
        $this->addFilterToMap('commission_percent', 'at_commission_percent.value');
        $this->addFilterToMap('livesearch_categories', 'at_livesearch_categories.value');
        $this->addFilterToMap('index_seller_id', 'at_index_seller_id.value');

        AbstractCollection::_initSelect();
    }

    public function addFieldToFilter($field, $condition = null){
        $this->_filtered[] = $field;
        parent::addFieldToFilter($field, $condition);
    }

    /**
     * Add select order
     *
     * @param string $field
     * @param string $direction
     *
     * @return $this
     */
    public function setOrder($field, $direction = self::SORT_ORDER_DESC)
    {
        $this->_filtered[] = $field;
        return parent::setOrder($field, $direction);
    }

    /**
     * Sets order and direction.
     *
     * @param string $field
     * @param string $direction
     *
     * @return $this
     */
    public function addOrder($field, $direction = self::SORT_ORDER_DESC)
    {
        $this->_filtered[] = $field;
        return parent::addOrder($field, $direction);
    }

    /**
     * Add select order to the beginning
     *
     * @param string $field
     * @param string $direction
     *
     * @return $this
     */
    public function unshiftOrder($field, $direction = self::SORT_ORDER_DESC)
    {
        $this->_filtered[] = $field;
        return parent::unshiftOrder($field, $direction);
    }

    /**
     * @inheritdoc
     */
    protected function _renderFiltersBefore(): void
    {
        $select = $this->getSelect();
        $fields = $this->_filtered;

        if (in_array('product_name', $fields)) {
            $nameAttribute = $this->getAttribute('name');
            $select->joinLeft(
                ['at_name' => $this->getTable('catalog_product_entity_' . $nameAttribute->getBackendType())],
                '`at_name`.`row_id` = `main_table`.`mage_pro_row_id` AND' .
                '`at_name`.`store_id` = 0 AND `at_name`.`attribute_id` = ' . $nameAttribute->getAttributeId(),
                ['product_name' => 'value', 'mage_store_id' => 'store_id']
            );
        }

        $visibilityAttribute = $this->getAttribute('visibility');
        $select->joinLeft(
            ['at_visibility' => $this->getTable('catalog_product_entity_' . $visibilityAttribute->getBackendType())],
            '`at_visibility`.`row_id` = `main_table`.`mage_pro_row_id` AND' .
            '`at_visibility`.`store_id` = 0 AND `at_visibility`.`attribute_id` = ' . $visibilityAttribute->getAttributeId(),
            ['visibility' => 'value']
        );

        $statusAttribute = $this->getAttribute('status');
        $select->joinLeft(
            ['at_status' => $this->getTable('catalog_product_entity_' . $statusAttribute->getBackendType())],
            '`at_status`.`row_id` = `main_table`.`mage_pro_row_id` AND' .
            '`at_status`.`store_id` = 0 AND `at_status`.`attribute_id` = ' . $statusAttribute->getAttributeId(),
            ['product_status' => 'value']
        );

        $costAttribute = $this->getAttribute('cost');
        $select->joinLeft(
            ['at_cost' => $this->getTable('catalog_product_entity_' . $costAttribute->getBackendType())],
            '`at_cost`.`row_id` = `main_table`.`mage_pro_row_id` AND' .
            '`at_cost`.`store_id` = 0 AND `at_cost`.`attribute_id` = ' . $costAttribute->getAttributeId(),
            ['cost' => new Expression('IFNULL(`at_cost`.`value`, 0)')]
        );

        $priceAttribute = $this->getAttribute('price');
        $select->joinLeft(
            ['at_price' => $this->getTable('catalog_product_entity_' . $priceAttribute->getBackendType())],
            '`at_price`.`row_id` = `main_table`.`mage_pro_row_id` AND' .
            '`at_price`.`store_id` = 0 AND `at_price`.`attribute_id` = ' . $priceAttribute->getAttributeId(),
            ['product_price' => new Expression('IFNULL(`at_price`.`value`, 0)')]
        );

        if (in_array('admin_user_updated', $fields)) {
            $updatedUserAttribute = $this->getAttribute('admin_user_updated');
            $select->joinLeft(
                ['at_admin_user_updated' => $this->getTable('catalog_product_entity_' . $updatedUserAttribute->getBackendType())],
                '`at_admin_user_updated`.`row_id` = `main_table`.`mage_pro_row_id` AND' .
                '`at_admin_user_updated`.`store_id` = 0 AND `at_admin_user_updated`.`attribute_id` = ' . $updatedUserAttribute->getAttributeId(),
                ['admin_user_updated' => 'value']
            );
        }

        $commissionRateAttribute = $this->getAttribute('commission_percent');
        $select->joinLeft(
            ['at_commission_percent' => $this->getTable('catalog_product_entity_' . $commissionRateAttribute->getBackendType())],
            '`at_commission_percent`.`row_id` = `main_table`.`mage_pro_row_id` AND' .
            '`at_commission_percent`.`store_id` = 0 AND `at_commission_percent`.`attribute_id` = ' . $commissionRateAttribute->getAttributeId(),
            ['commission_percent' => new Expression('IFNULL(`at_commission_percent`.`value`, 0)')]
        );

        if (in_array('livesearch_categories', $fields)) {
            $livesearchCategories = $this->getAttribute('livesearch_categories');
            $select->joinLeft(
                ['at_livesearch_categories' => $this->getTable('catalog_product_entity_' . $livesearchCategories->getBackendType())],
                '`at_livesearch_categories`.`row_id` = `main_table`.`mage_pro_row_id` AND' .
                '`at_livesearch_categories`.`store_id` = 0 AND `at_livesearch_categories`.`attribute_id` = ' . $livesearchCategories->getAttributeId(),
                ['livesearch_categories' => 'value']
            );
        }

        if (in_array('index_seller_id', $fields)) {
            $indexSellerIdAttribute = $this->getAttribute('index_seller_id');
            $select->joinLeft(
                ['at_index_seller_id' => $this->getTable('catalog_product_entity_' . $indexSellerIdAttribute->getBackendType())],
                '`at_index_seller_id`.`row_id` = `main_table`.`mage_pro_row_id` AND' .
                '`at_index_seller_id`.`store_id` = 0 AND `at_index_seller_id`.`attribute_id` = ' . $indexSellerIdAttribute->getAttributeId(),
                ['index_seller_id' => 'value']
            );
        }

        $select->joinLeft(
            ['at_customer' => $this->getTable('customer_grid_flat')],
            '`at_customer`.`entity_id` = `main_table`.`seller_id`',
            ['name']
        );

        $select->joinLeft(
            ['at_flags' => $this->getTable('marketplace_productflags')],
            '`at_flags`.`product_id` = `main_table`.`mageproduct_id`',
            ['flagcount' => 'count(`at_flags`.`entity_id`)']
        );

        $catalogInventoryStockItem = $this->getTable('cataloginventory_stock_item');
        $this->getSelect()->join(
            $catalogInventoryStockItem.' as csi',
            'main_table.mageproduct_id = csi.product_id',
            ["qty" => "qty"]
        )->where("csi.website_id = 0 OR csi.website_id = 1");
        $select->group('mageproduct_id');
    }

    /**
     * Retrieve product attribute by code.
     *
     * @param string $attributeCode
     *
     * @return ProductAttributeInterface|null
     */
    private function getAttribute(string $attributeCode): ?ProductAttributeInterface
    {
        try {
            return $this->productAttributeRepository->get($attributeCode);
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }
}
