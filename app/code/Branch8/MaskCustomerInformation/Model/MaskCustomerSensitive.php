<?php
declare(strict_types=1);

namespace Branch8\MaskCustomerInformation\Model;

use Magento\Framework\DataObject;

class MaskCustomerSensitive extends DataObject
{
    const FLAG = 'SECURE_CUSTOMER_DATA';
    const SECURE_RULE_PROCESS = 'SECURE_RULE_PROCESS';

    /**
     * @param array $collections
     * @return array
     */
    public function sortCollectionRules(array $collections)
    {
        $sortedCollections = [];
        foreach ($collections as $entity => $subs) {
            if (is_array($subs)) {
                foreach ($subs as $sub) {
                    $sortedCollections[$sub] = $entity;
                }
            }
        }
        return $sortedCollections;
    }


    /**
     * @param \Magento\Framework\Data\Collection $collection
     * @param RuleInterface $rule
     * @return \Magento\Framework\Data\Collection
     */
    public function applyRule(\Magento\Framework\Data\Collection $collection, RuleInterface $rule)
    {
        /**
         * @var $item \Magento\Sales\Model\Order
         */
        foreach ($collection as $item) {
            $item->setData(
                $rule->apply($item->getData())
            );
        }
        return $collection;
    }
}
