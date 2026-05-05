<?php

namespace Branch8\PromotionRule\Model\Actions;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\Store;
use Magento\Quote\Model\Quote\Item;

class GetCurrentAffectCatalogRuleForProducts
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;
    /**
     * @var TimezoneInterface
     */
    private $dateTime;

    /**
     * @param ResourceConnection $resourceConnection
     * @param TimezoneInterface $dateTime
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        TimezoneInterface  $dateTime
    )
    {
        $this->dateTime = $dateTime;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param Item $item
     * @param Store $store
     * @param $customerGroupId
     * @return array
     */
    public function get(Item $item, Store $store, $customerGroupId)
    {
        $catalogRuleIds = [];
        $product = $item->getProduct();
        $date = $this->dateTime->scopeDate($store->getId());
        if (!$item->getPrice()) {
            return $catalogRuleIds;
        }
        $rules = $this->getRulePrices(
            $this->dateTime->scopeDate($store->getId()),
            $store->getWebsiteId(),
            $customerGroupId,
            [$product->getId()],
            $product->getFinalPrice()
        );
        if ($rules) {
            $catalogRuleIds = $this->getMatchedCatalogRuleIds(
                $product->getId(),
                $customerGroupId,
                $store->getWebsiteId(),
                $date
            );
        }
        return array_unique($catalogRuleIds);
    }

    /**
     * @param \DateTimeInterface $date
     * @param $websiteId
     * @param $customerGroupId
     * @param $productIds
     * @param $price
     * @return mixed
     */
    private function getRulePrices(\DateTimeInterface $date, $websiteId, $customerGroupId, $productIds, $price)
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from($connection->getTableName('catalogrule_product_price'),
                ['rule_product_price_id', 'product_id', 'rule_price'])
            ->where('rule_date = ?', $date->format('Y-m-d'))
            ->where('website_id = ?', $websiteId)
            ->where('customer_group_id = ?', $customerGroupId)
            ->where('product_id IN(?)', $productIds, \Zend_Db::INT_TYPE)
            ->where('rule_price = (?)', $price);
        return $connection->fetchRow($select);
    }

    /**
     * @param $productId
     * @param $customerGroupId
     * @param $websiteId
     * @param $date
     * @return array
     */
    private function getMatchedCatalogRuleIds($productId, $customerGroupId, $websiteId, $date)
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('catalogrule_product');
        $select = $connection->select()
            ->from($table, ['rule_id'])
            ->where('product_id = ?', $productId)
            ->where('customer_group_id = ?', $customerGroupId)
            ->where('website_id = ?', $websiteId);
        $ruleIds = $connection->fetchCol($select);
        return $ruleIds ?: [];
    }
}
