<?php

namespace Branch8\PromotionRule\Model\Actions;

use Magento\Framework\App\ResourceConnection;

class GetRulePrices
{
    private $rulePrices = [];

    private ResourceConnection $resourceConnection;

    /**
     * @param ResourceConnection $resource
     */
    public function __construct(ResourceConnection $resource)
    {
        $this->resourceConnection = $resource;
    }

    /**
     * @param \DateTimeInterface $date
     * @param $websiteId
     * @param $customerGroupId
     * @param $productIds
     * @return array
     */
    public function getRulePriceForProducts(\DateTimeInterface $date, $websiteId, $customerGroupId, $productIds)
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from($connection->getTableName('catalogrule_product_price'),
                ['rule_product_price_id', 'product_id', 'rule_price'])
            ->where('rule_date = ?', $date->format('Y-m-d'))
            ->where('website_id = ?', $websiteId)
            ->where('customer_group_id = ?', $customerGroupId)
            ->where('product_id IN(?)', $productIds, \Zend_Db::INT_TYPE);
        $rows = $connection->fetchAll($select);
        if ($rows) {
            foreach ($rows as $row) {
                $this->rulePrices[$row['product_id']] = $row;
            }
        }
        return $this->rulePrices;
    }

    /**
     * @param \DateTimeInterface $date
     * @param $websiteId
     * @param $customerGroupId
     * @param $productId
     * @param $price
     * @return mixed
     */
    public function getRulePricesForProduct(\DateTimeInterface $date, $websiteId, $customerGroupId, $productId)
    {
        if (isset($this->rulePrices[$productId])) {
            return $this->rulePrices[$productId];
        }
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from($connection->getTableName('catalogrule_product_price'),
                ['rule_price'])
            ->where('rule_date = ?', $date->format('Y-m-d'))
            ->where('website_id = ?', $websiteId)
            ->where('customer_group_id = ?', $customerGroupId)
            ->where('product_id IN(?)', [$productId], \Zend_Db::INT_TYPE);
        $row = $connection->fetchOne($select);
        if ($row) {
            $this->rulePrices[$productId] = $row;
        } else {
            $this->rulePrices[$productId] = 0;
        }
        return $this->rulePrices[$productId];
    }
}
