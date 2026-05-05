<?php

namespace Branch8\Sales\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SaveRuleName implements ObserverInterface
{
    /**
     * @var \Magento\SalesRule\Api\RuleRepositoryInterface
     */
    protected $ruleRepositoryInterface;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resource;

    public function __construct(
        \Magento\SalesRule\Api\RuleRepositoryInterface $ruleRepositoryInterface,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->resource = $resource;
        $this->ruleRepositoryInterface = $ruleRepositoryInterface;
    }

    /**
     * @param  Observer  $observer
     * Save the names of the rules applied to the order.
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        if ($order->getAppliedRuleIds()) {
            $ruleIds = explode(',', $order->getAppliedRuleIds());
            $ruleNames = [];
            foreach ($ruleIds as $ruleId) {
                $rule = $this->ruleRepositoryInterface->getById($ruleId);
                $ruleNames[] = $rule->getName();
            }
            $connection = $this->resource->getConnection();
            $connection->update('sales_order', ['applied_rule_names' => implode(',', $ruleNames)],
                ['entity_id = ?' => $order->getId()]);
        }
    }
}
