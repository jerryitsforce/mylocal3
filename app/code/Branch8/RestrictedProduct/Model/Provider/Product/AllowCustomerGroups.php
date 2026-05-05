<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\RestrictedProduct\Model\Provider\Product;

use Branch8\RestrictedProduct\Helper\Data as DataHelper;
use Magento\Customer\Model\ResourceModel\Group\Collection as CustomerGroup;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\Product;

class AllowCustomerGroups
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $localeDate;

    /**
     * @var CustomerGroup
     */
    protected $customerGroup;

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var EavConfig
     */
    private EavConfig $eavConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        CustomerGroup $customerGroup,
        ResourceConnection $resourceConnection,
        EavConfig $config,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->customerGroup = $customerGroup;
        $this->localeDate = $localeDate;
        $this->resourceConnection = $resourceConnection;
        $this->eavConfig = $config;
        $this->storeManager = $storeManager;
    }

    /**
     * @param array $feedItems
     * @return array
     * @throws \Zend_Db_Select_Exception
     * @throws \Zend_Db_Statement_Exception
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get(array $feedItems): array
    {
        $output = [];
        $ids = [];
        $data = [];
        
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/allow_customer_groups.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        // Initialize output and group by store
        $logger->info("Batch Size: " . count($feedItems));
        $customerId = $this->getCustomerGroupsIds();
        $customGroupValue = implode(',', $customerId);
        $attribute = $this->eavConfig->getAttribute(Product::ENTITY, 'allow_customer_groups');
        
        // Collect IDs per Store
        foreach ($feedItems as $value) {
            $ids[$value['storeViewCode']][] = $value['productId'];
            
            // Default value (fallback if no attribute value found)
            $defaultValue = $value;
            $defaultValue['allow_customer_groups'] = $customGroupValue;
            $output[$this->getKey($value)] = $this->format($defaultValue);
        }

        if (!$attribute || !$attribute->getId()) {
             return $output;
        }

        $connection = $this->resourceConnection->getConnection();
        
        foreach ($ids as $storeCode => $productIds) {
             if (empty($productIds)) {
                 continue;
             }
             
             // Fetch attribute values directly from DB (since indexer already updated them)
             // We join catalog_product_entity to get row_id if needed, but for varchar table we usually need row_id
             // However, to keep it simple and robust, we can query catalog_product_entity_varchar joined with catalog_product_entity
             
             $select = $connection->select()
                ->from(['v' => $this->resourceConnection->getTableName('catalog_product_entity_varchar')], ['value'])
                ->join(['e' => $this->resourceConnection->getTableName('catalog_product_entity')], 'v.row_id = e.row_id', ['entity_id'])
                ->where('v.attribute_id = ?', $attribute->getId())
                ->where('v.store_id = ?', 0) // Attribute is global
                ->where('e.entity_id IN (?)', $productIds);
             
             $values = $connection->fetchPairs($select); // [entity_id => value]
             
             foreach ($productIds as $pId) {
                 if (isset($values[$pId])) {
                     $saveValue = [
                        'productId' => $pId,
                        'storeViewCode' => $storeCode,
                        'allow_customer_groups' => $values[$pId]
                     ];
                     $output[$this->getKey($saveValue)] = $this->format($saveValue);
                 }
             }
        }

        return $output;
    }

    private function getCustomerGroupsIds()
    {
        $customerGroups = $this->customerGroup->toOptionArray();
        return array_values(array_column($customerGroups,'value'));
    }



    /**
     * Format output
     *
     * @param array $row
     * @return array
     */
    private function format(array $row) : array
    {
        // Revert to string format to fix "Array to string conversion" error in DataExporter Transformer
        // Code expects a scalar string here, even for multi-select attributes apparently
        $groups = $row['allow_customer_groups'];
        if (is_array($groups)) {
             $groups = implode(',', $groups);
        }

        return [
            'productId' => $row['productId'],
            'storeViewCode' => $row['storeViewCode'],
            'allow_customer_groups' => (string)$groups,
        ];
    }

    /**
     * @param array $item
     * @return string
     */
    private function getKey(array $item): string
    {
        return $item['productId'] . '-' . $item['storeViewCode'];
    }
}
