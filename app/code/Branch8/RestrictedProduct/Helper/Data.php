<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\RestrictedProduct\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config;
use Amasty\Groupcat\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use Magento\Customer\Model\ResourceModel\Group\Collection as CustomerGroup;

use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const ATTRIBUTE_CODE_RESTRICTED_CUSTOMER_GROUPS = "allow_customer_groups";

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;
    
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resource;

    /**
     * @var RuleCollectionFactory
     */
    protected $ruleCollectionFactory;


    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connection;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $localeDate;

    /**
     * @var CustomerGroup
     */
    protected $customerGroup;

    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $config;

    /**
     * @param Context $context
     */
    public function __construct(
        Context $context,
        RuleCollectionFactory $ruleCollectionFactory,
        CustomerGroup $customerGroup,
        Config $config,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
    )
    {
        $this->resource              = $resource;
        $this->connection            = $resource->getConnection();
        $this->ruleCollectionFactory = $ruleCollectionFactory;
        $this->customerGroup         = $customerGroup;
        $this->config                = $config;
        $this->productRepository     = $productRepository; 
        $this->localeDate            = $localeDate;
        parent::__construct($context);
    }

    public function processUpdateProductFull() {
        // Process in chunks to avoid memory issues
        $batchSize = 1000;
        $page = 1;
        $productTableName = $this->resource->getTableName('catalog_product_entity');
        
        while (true) {
            $select = $this->connection->select()
                ->from($productTableName, 'entity_id')
                ->limitPage($page, $batchSize);
            
            $ids = $this->connection->fetchCol($select);
            
            if (empty($ids)) {
                break;
            }
            
            $this->processUpdateProduct($ids);
            $page++;
        }
    }

    public function processUpdateProduct($ids) {
        if (empty($ids)) {
            return;
        }

        // Determine if ids are Product IDs or Rule IDs
        // Mview passes Rule IDs for 'amasty_groupcat_rule' subscription, but Product IDs for others.
        // Optimized ID gathering: Use UNION instead of separate fetches + array merge
        $unionIdSelect = $this->connection->select()
            ->union([
                $this->connection->select()
                    ->from($this->resource->getTableName('amasty_groupcat_rule_product'), 'product_id')
                    ->where('rule_id IN (?)', $ids),
                $this->connection->select()
                    ->from(['rc' => $this->resource->getTableName('amasty_groupcat_rule_category')], [])
                    ->join(['ccp' => $this->resource->getTableName('catalog_category_product')], 'rc.category_id = ccp.category_id', ['product_id'])
                    ->where('rc.rule_id IN (?)', $ids)
            ]);
        
        $relatedProductIds = $this->connection->fetchCol($unionIdSelect);
        $productIds = array_unique(array_merge($ids, $relatedProductIds));
        
        $customGroup = $this->getCustomerGroupsIds();

        // Chunk the processing of these product IDs to avoid huge SQL queries
        $chunks = array_chunk($productIds, 1000);
        foreach ($chunks as $chunkIds) {
            $this->processProductChunk($chunkIds, $customGroup);
        }
    }

    private function processProductChunk($productIds, $customGroup) {

        $date = $this->localeDate->date()->format('Y-m-d');

        // Prepare Direct Product Rules Query
        $tableName = $this->resource->getTableName('amasty_groupcat_rule_product');
        $selectDirect = $this->connection->select()
            ->from(['product' => $tableName], [])
            ->joinLeft(['rule' => $this->resource->getTableName('amasty_groupcat_rule')], 'rule.rule_id = product.rule_id', [])
            ->joinLeft(['rule_group' => $this->resource->getTableName('amasty_groupcat_rule_customer_group')], 'rule_group.rule_id = product.rule_id', [])
            ->columns([
                'product_id' => 'product.product_id',
                'priority' => 'rule.priority',
                'rule_id' => 'rule.rule_id',
                'customer_group_ids' => new \Magento\Framework\DB\Sql\Expression('GROUP_CONCAT(rule_group.customer_group_id)')
            ])
            ->where('from_date is null or from_date < ?', $date)
            ->where('to_date is null or to_date > ?', $date)
            ->where('rule.is_active = 1')
            ->where('product.hide_product = 1')
            ->where('product.product_id IN (?)', $productIds)
            ->group(['product.product_id', 'product.rule_id']);

        // Prepare Category Rules Query
        $selectCat = $this->connection->select()
            ->from(['ccp' => $this->resource->getTableName('catalog_category_product')], [])
            ->join(['rc' => $this->resource->getTableName('amasty_groupcat_rule_category')], 'ccp.category_id = rc.category_id', [])
            ->join(['r' => $this->resource->getTableName('amasty_groupcat_rule')], 'rc.rule_id = r.rule_id', [])
            ->join(['rg' => $this->resource->getTableName('amasty_groupcat_rule_customer_group')], 'r.rule_id = rg.rule_id', [])
            ->join(['rs' => $this->resource->getTableName('amasty_groupcat_rule_store')], 'r.rule_id = rs.rule_id', [])
            ->columns([
                'product_id' => 'ccp.product_id',
                'priority' => 'r.priority',
                'rule_id' => 'r.rule_id',
                'customer_group_ids' => new \Magento\Framework\DB\Sql\Expression('GROUP_CONCAT(rg.customer_group_id)')
            ])
            ->where('r.hide_product = 1')
            ->where('r.hide_category = 1')
            ->where('r.is_active = 1')
            ->where('r.from_date is null or r.from_date < ?', $date)
            ->where('r.to_date is null or r.to_date > ?', $date)
            ->where('ccp.product_id IN (?)', $productIds)
            ->group(['ccp.product_id', 'r.rule_id']);

        // UNION ALL
        $unionSelect = $this->connection->select()->union([$selectDirect, $selectCat], \Magento\Framework\DB\Select::SQL_UNION_ALL);
        $unionSql = $unionSelect->__toString();

        // Window Function ROW_NUMBER()
        $finalSql = "
            SELECT * FROM (
                SELECT 
                    inner_u.*, 
                    ROW_NUMBER() OVER (PARTITION BY product_id ORDER BY priority DESC, rule_id DESC) as rn 
                FROM ($unionSql) as inner_u
            ) as ranked 
            WHERE rn = 1
        ";

        $cursor = $this->connection->query($finalSql);
        $data = [];
        $calculatedGroups = [];
        
        while ($row = $cursor->fetch()) {
            $pId = $row['product_id'];
            if (!$pId || $row['customer_group_ids'] === null || $row['customer_group_ids'] === '') {
                 continue;
            }

            $ruleKey = $row['customer_group_ids'];
            if (isset($calculatedGroups[$ruleKey])) {
                $allowGroups = $calculatedGroups[$ruleKey];
            } else {
                $restrictedGroups = explode(',', $ruleKey);
                $restrictedGroups = array_unique($restrictedGroups);

                // If product is restricted for ANY group, it must strictly be restricted for GUEST (0) as well.
                if (!empty($restrictedGroups)) {
                    if (!in_array('0', $restrictedGroups) && !in_array(0, $restrictedGroups)) {
                        $restrictedGroups[] = '0';
                    }
                }

                $allowGroups = array_diff($customGroup, $restrictedGroups);
                $calculatedGroups[$ruleKey] = $allowGroups;
            }
            
            $data[$pId] = $allowGroups;
        }

        // Handle products that have no matching rules in this chunk (should be allowed for all)
        foreach ($productIds as $productId) {
            if (!isset($data[$productId])) {
                $data[$productId] = $customGroup;
            }
        }

        if (count($data)) {
            $this->updateRestrictedCustomerGroupsSql($data);
        }
    }

    public function updateRestrictedCustomerGroupsSql($dataCustomGroup){
        $attribute = $this->config->getAttribute(Product::ENTITY, self::ATTRIBUTE_CODE_RESTRICTED_CUSTOMER_GROUPS);
        if(!$attribute->getId()){
            return;
        }

        $table = $this->connection->getTableName('catalog_product_entity_varchar');
        $ids = array_keys($dataCustomGroup);
        
        if (empty($ids)) {
            return;
        }

        // Optimized row_id fetch
        $select = $this->connection->select()
            ->from('catalog_product_entity', ['entity_id', 'row_id'])
            ->where('entity_id IN (?)', $ids);
            
        $rowIdMap = $this->connection->fetchPairs($select);

        $data = [];
        foreach ($dataCustomGroup as $entityId => $groups) {
            if (isset($rowIdMap[$entityId])) {
                $data[] = [
                    'store_id' => 0, // global scope
                    'row_id' => $rowIdMap[$entityId],
                    'attribute_id' => $attribute->getId(),
                    'value' => implode(',', $groups)
                ];
            }
        }

        if ($data) {
            $this->connection->insertOnDuplicate($table, $data);
        }
    }

    public function updateRestrictedCustomerGroups($productId, $customGroup){
        $customGroup = implode(',',$customGroup);
        $product = $this->productRepository->getById($productId);
        if($product->getId()){
            $product->setData(self::ATTRIBUTE_CODE_RESTRICTED_CUSTOMER_GROUPS, $customGroup);
            $product->getResource()->saveAttribute($product, self::ATTRIBUTE_CODE_RESTRICTED_CUSTOMER_GROUPS);
        }
    }

    public function getCustomerGroupsIds()
    {
        $customerGroups = $this->customerGroup->toOptionArray();
        return array_values(array_column($customerGroups,'value'));
    }

}