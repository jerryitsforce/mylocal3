<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model\ResourceModel\ProductApprovalManagement\Grid;

use Magento\Catalog\Api\Data\ProductAttributeInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\View\Element\UiComponent\DataProvider\Document;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Webkul\Marketplace\Model\Product;

class Collection extends \Webkul\Marketplace\Model\ResourceModel\Product\Grid\Collection
{
    /**
     * @var ProductAttributeRepositoryInterface
     */
    private ProductAttributeRepositoryInterface $productAttributeRepository;

    /**
     * @var AuthorizationInterface
     */
    protected AuthorizationInterface $authorization;

    /**
     * Collection constructor.
     *
     * @param EntityFactoryInterface $entityFactory
     * @param LoggerInterface $logger
     * @param FetchStrategyInterface $fetchStrategy
     * @param ManagerInterface $eventManager
     * @param StoreManagerInterface $storeManager
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     * @param AuthorizationInterface $authorization
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
        AuthorizationInterface              $authorization,
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
        $this->authorization = $authorization;
        $this->productAttributeRepository = $productAttributeRepository;
    }

    /**
     * @inheritdoc
     */
    protected function _initSelect(): void
    {
        $this->addFilterToMap('id', 'at_product.entity_id');
        $this->addFilterToMap('sku', 'at_product.sku');
        $this->addFilterToMap('product_name', 'at_product_version.product_name');
        $this->addFilterToMap('product_status', 'at_status.value');
        $this->addFilterToMap('mage_store_id', 'at_name.store_id');
        $this->addFilterToMap('created_at', 'main_table.created_at');
        $this->addFilterToMap('updated_at', 'main_table.updated_at');
        $this->addFilterToMap('cost', 'at_cost.value');
        $this->addFilterToMap('product_price', 'at_price.value');
        $this->addFilterToMap('name', 'marketplace_user.shop_title');  // 修改：改為 shop_title
        $this->addFilterToMap('submission_time', 'at_product_version.created_at');
        $this->addFilterToMap('first_enabled_date', 'at_first_enabled_date.value');
        $this->addFilterToMap('tax_class', 'at_tax_class.value');
        $this->addFilterToMap('seller_id', 'main_table.seller_id');
        $this->addFilterToMap('admin_user_updated', 'at_admin_user_updated.value');

        AbstractCollection::_initSelect();
    }

    /**
     * @inheritdoc
     *
     * @throws \Zend_Db_Select_Exception
     */
    protected function _renderFiltersBefore(): void
    {
        $select = $this->getSelect();
        $this->modifyWhereConditions($select);
        //get sku and id
        $select->joinLeft(
            ['at_product' => $this->getTable('catalog_product_entity')],
            '`at_product`.`entity_id` = `main_table`.`mageproduct_id`',
            ['sku', 'id' => 'entity_id']
        );

        $nameAttribute = $this->getAttribute('name');
        $select->joinLeft(
            ['at_name' => $this->getTable('catalog_product_entity_' . $nameAttribute->getBackendType())],
            '`at_name`.`row_id` = `main_table`.`mage_pro_row_id` AND' .
            '`at_name`.`store_id` = 0 AND `at_name`.`attribute_id` = ' . $nameAttribute->getAttributeId(),
            ['product_name' => 'value', 'product_name_item' => 'value', 'mage_store_id' => 'store_id']
        );

        $statusAttribute = $this->getAttribute('status');
        $select->joinLeft(
            ['at_status' => $this->getTable('catalog_product_entity_' . $statusAttribute->getBackendType())],
            '`at_status`.`row_id` = `main_table`.`mage_pro_row_id` AND' .
            '`at_status`.`store_id` = 0 AND `at_status`.`attribute_id` = ' . $statusAttribute->getAttributeId(),
            ['product_status' => 'value']
        );

        $visibilityAttribute = $this->getAttribute('visibility');
        $select->joinLeft(
            ['at_visibility' => $this->getTable('catalog_product_entity_' . $visibilityAttribute->getBackendType())],
            '`at_visibility`.`row_id` = `main_table`.`mage_pro_row_id` AND' .
            '`at_visibility`.`store_id` = 0 AND `at_visibility`.`attribute_id` = ' . $visibilityAttribute->getAttributeId(),
            ['visibility' => 'value']
        );

        $priceAttribute = $this->getAttribute('price');
        $select->joinLeft(
            ['at_price' => $this->getTable('catalog_product_entity_' . $priceAttribute->getBackendType())],
            '`at_price`.`row_id` = `main_table`.`mage_pro_row_id` AND' .
            '`at_price`.`store_id` = 0 AND `at_price`.`attribute_id` = ' . $priceAttribute->getAttributeId(),
            ['product_price' => 'value']
        );

        $specialPriceAttribute = $this->getAttribute('special_price');
        $select->joinLeft(
            ['at_special_price' => $this->getTable('catalog_product_entity_' . $specialPriceAttribute->getBackendType())],
            '`at_special_price`.`row_id` = `main_table`.`mage_pro_row_id` AND' .
            '`at_special_price`.`store_id` = 0 AND `at_special_price`.`attribute_id` = ' . $specialPriceAttribute->getAttributeId(),
            ['special_price' => 'value']
        );

        $costAttribute = $this->getAttribute('cost');
        $select->joinLeft(
            ['at_cost' => $this->getTable('catalog_product_entity_' . $costAttribute->getBackendType())],
            '`at_cost`.`row_id` = `main_table`.`mage_pro_row_id` AND' .
            '`at_cost`.`store_id` = 0 AND `at_cost`.`attribute_id` = ' . $costAttribute->getAttributeId(),
            ['cost' => 'value']
        );

        $taxClassAttribute = $this->getAttribute('tax_class_id');
        $select->joinLeft(
            ['at_tax_class' => $this->getTable('catalog_product_entity_' . $taxClassAttribute->getBackendType())],
            '`at_tax_class`.`row_id` = `main_table`.`mage_pro_row_id` AND' .
            '`at_tax_class`.`store_id` = 0 AND `at_tax_class`.`attribute_id` = ' . $taxClassAttribute->getAttributeId(),
            ['tax_class' => 'value']
        );

        $firstEnabledDateAttribute = $this->getAttribute('first_enabled_date');
        $select->joinLeft(
            ['at_first_enabled_date' => $this->getTable('catalog_product_entity_' . $firstEnabledDateAttribute->getBackendType())],
            '`at_first_enabled_date`.`row_id` = `main_table`.`mage_pro_row_id` AND' .
            '`at_first_enabled_date`.`attribute_id` = ' . $firstEnabledDateAttribute->getAttributeId(),
            ['first_enabled_date' => 'value']
        );

        $updatedUserAttribute = $this->getAttribute('admin_user_updated');
        $select->joinLeft(
            ['at_admin_user_updated' => $this->getTable('catalog_product_entity_' . $updatedUserAttribute->getBackendType())],
            '`at_admin_user_updated`.`row_id` = `main_table`.`mage_pro_row_id` AND' .
            '`at_admin_user_updated`.`store_id` = 0 AND `at_admin_user_updated`.`attribute_id` = ' . $updatedUserAttribute->getAttributeId(),
            ['admin_user_updated' => 'value']
        );

        // 修改：join marketplace_userdata 取得 shop_title，並命名為 name
        $select->joinLeft(
            ['marketplace_user' => $this->getTable('marketplace_userdata')],
            '`marketplace_user`.`seller_id` = `main_table`.`seller_id`',
            ['name' => 'shop_title']
        );

        $select->joinLeft(
            ['stock' => $this->getTable('cataloginventory_stock_item')],
            '`stock`.`product_id` = `main_table`.`mageproduct_id`',
            ['qty']
        );

        $select->joinLeft(
            ['at_product_version' => $this->getTable('marketplace_product_version')],
            '`at_product_version`.`product_id` = `main_table`.`mageproduct_id` AND' .
            '`at_product_version`.`status` = 0',
            [
                'submission_time' => 'created_at',
                'created_from' => 'created_from',
                'product_name' => 'product_name',
                'new_product_price' => new Expression('COALESCE(`at_product_version`.`price`, `at_price`.`value`, 0)'),
                'new_special_price' => new Expression('COALESCE(`at_product_version`.`special_price`, `at_special_price`.`value`, 0)'),
                'new_cost' => new Expression('COALESCE(`at_product_version`.`cost`, `at_cost`.`value`, 0)'),
                'additional_information' => 'additional_information',
                'is_new_product' => new Expression('IF(at_product_version.created_at is null OR at_product_version.is_new_product, 1, 0)'),
                'is_cost_changed' => 'is_cost_changed',
                'is_special_price_changed' => 'is_special_price_changed',
                'is_price_cost_changed' => 'is_price_cost_changed',
                'gross_profit' => 'commission_percent',
                'approval_flow_status' => 'approval_flow_status',
                'approval_log' => 'approval_log'
            ]
        );

        $select->joinLeft(
            ['at_initial' => $this->getTable('product_initial_info')],
            '`at_initial`.`product_id` = `main_table`.`mageproduct_id`',
            [
                'initial_price' => 'price',
                'initial_special_price' => 'special_price',
                'initial_cost' => 'cost',
                'init_gross_profit' => 'init_gross_profit'
            ]
        );

        $select->joinLeft(
            ['at_option' => $this->getTable('catalog_product_option')],
            '`at_option`.`product_id` = `main_table`.`mage_pro_row_id`',
            [
                'option_id' => 'option_id'
            ]
        );

        //join seller to get config
        $select->joinLeft(
            ['at_partner' => $this->getTable('marketplace_saleperpartner')],
            '`at_partner`.`seller_id` = `main_table`.`seller_id`',
            [
                'base_gross_profit' => 'min_commission_rate',
                'gross_profit_level' => 'commission_rate'
            ]
        );

        if (!$this->authorization->isAllowed('Branch8_MarketplaceProduct::approve')) {
            $this->getSelect()->where('`main_table`.`dealer_approve_status` = ?', Product::STATUS_PENDING);
        }
        
        $select->where('`main_table`.`status` = ?', Product::STATUS_PENDING)
            ->group('mageproduct_id');
//        echo $select;die;
        $this->getSelect()->columns([
            'product_name' => new \Zend_Db_Expr('IF(at_product_version.product_name IS NOT NULL, at_product_version.product_name, at_name.value)')
        ]);
    }

    public function setOrder($field, $direction = 'DESC')
    {
        if ($field === 'product_name') {
            $field = 'at_product_version.product_name';
        }
        parent::setOrder($field, $direction); // TODO: Change the autogenerated stub
    }

    /**
     * Modify where conditions.
     *
     * @param Select $select
     *
     * @return void
     *
     * @throws \Zend_Db_Select_Exception
     */
    private function modifyWhereConditions(Select $select): void
    {
        $wherePart = $select->getPart(\Zend_Db_Select::WHERE);

        if (!empty($wherePart)) {
            foreach ($wherePart as $key => $condition) {
                if (strpos($condition, 'additional_information') !== false && strpos($condition, 'JSON_EXTRACT') === false) {
                    $condition = str_replace(
                        "`additional_information` LIKE '%",
                        'JSON_EXTRACT(additional_information, "$.',
                        $condition
                    );
                    $condition = str_replace(
                        "%'",
                        '")',
                        $condition
                    );
                    $wherePart[$key] = $condition.' IS NOT NULL';
                }
            }
            $replaceMap = [
                '`at_price`.`value`' => 'COALESCE(at_product_version.price, at_price.value, 0)',
                '`at_special_price`.`value`' => 'COALESCE(at_product_version.special_price, at_special_price.value, 0)',
                '`at_cost`.`value`' => 'COALESCE(at_product_version.cost, at_cost.value, 0)',
            ];

            $whereConditions = implode(
                ' ',
                array_map(
                    fn($condition) => strtr($condition, $replaceMap),
                    $wherePart
                )
            );

            $select->reset(\Zend_Db_Select::WHERE);
            $select->where($whereConditions);
        }
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