<?php

namespace Branch8\PromotionRule\Model\Actions;

use Magento\Framework\App\ResourceConnection;

class GetSellerBorneCatalogRuleConfig
{
    private ResourceConnection $resource;

    /**
     * @param ResourceConnection $resource
     */
    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    /**
     * @param $ruleIds
     * @return array
     */
    public function execute($ruleIds)
    {
        $select = $this->resource->getConnection()->select()->from(
            'catalogrule',
            [
                'rule_id' => 'rule_id',
                'seller_borne_discount' => 'seller_borne_discount',
                'seller_ids' => 'seller_ids',
                'borne_discount_percentage' => 'borne_discount_percentage'
            ]
        )->where('rule_id in (?)', $ruleIds)
            ->where('is_active = ?', 1);
        return $this->resource->getConnection()->fetchAll($select);
    }
}
