<?php
namespace Branch8\CatalogRule\Plugin;

class PromoRuleApprovalStatus
{

    public function beforeLoad(\Magento\Framework\Data\Collection\AbstractDb $collection, $printQuery = false, $logQuery = false)
    {
        if ($collection instanceof \Magento\CatalogRule\Model\ResourceModel\Grid\Collection) {
            $select = $collection->getSelect();
            if (!isset($select->getPart('from')['appr'])) {
                $select->joinLeft(
                    ['appr' => $collection->getTable('catalogrule_approval')],
                    'main_table.rule_id = appr.catalogrule_id and appr.status=0',
                    ['approval_id' => 'appr.entity_id', 'post_data']
                );
            }
            
        }

        return [$printQuery, $logQuery];
    }
}
