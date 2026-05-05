<?php

namespace Branch8\MaskCustomerInformation\Observer;

use Branch8\MaskCustomerInformation\Model\GlobalPermission;
use Branch8\MaskCustomerInformation\Model\MaskCustomerSensitive;
use Branch8\MaskCustomerInformation\Model\PermissionInterface;
use Branch8\MaskCustomerInformation\Model\RuleInterface;
use Branch8\MaskCustomerInformation\Model\RuleManagement;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class HideSensitiveDefaultObserver implements ObserverInterface
{
    private array $collectionChecks;
    private PermissionInterface $permission;

    private RuleManagement $ruleManagement;

    private $sortedCollections = null;
    private MaskCustomerSensitive $maskCustomerSensitive;

    private LoggerInterface $logger;

    private array $ignoreClasses;

    /**
     * @param PermissionInterface $permission
     * @param RuleManagement $ruleManagement
     * @param MaskCustomerSensitive $maskCustomerSensitive
     * @param LoggerInterface $logger
     * @param array $collectionChecks
     * @param array $ignoreClasses
     */
    public function __construct(
        PermissionInterface   $permission,
        RuleManagement        $ruleManagement,
        MaskCustomerSensitive $maskCustomerSensitive,
        LoggerInterface       $logger,
        array                 $collectionChecks = [],
        array                 $ignoreClasses = []
    )
    {
        $this->maskCustomerSensitive = $maskCustomerSensitive;
        $this->collectionChecks = $collectionChecks;
        $this->permission = $permission;
        $this->logger=$logger;
        $this->ruleManagement = $ruleManagement;
        $this->ignoreClasses = $ignoreClasses;
    }

    /**
     * @return array
     */
    private function getSortedCollections()
    {
        if ($this->sortedCollections === null) {
            $this->sortedCollections = $this->maskCustomerSensitive->sortCollectionRules($this->collectionChecks);
        }
        return $this->sortedCollections;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @throws \Magento\Framework\Validator\Exception
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $specialTables = [
            'sales_invoice_grid'
        ];
        /**
         * @var $collection \Magento\Framework\Data\Collection
         */
        $collection = $observer->getCollection();
        $sortedCollections = $this->getSortedCollections();
        $class = get_class($collection);
        if (in_array($class, $this->ignoreClasses)) {
            return;
        }
        //$this->logger->info('CLASS:'.$class);
        /**
         * @TODO  need new solution to prevent process two times
         */
        if ($this->permission->canView()) {
            return;
        }
        // case have secure flag; see m2-hotfixes/4.11.1.1-add-flag+rule-secure-when-load-item.patch
        if (method_exists($collection, 'getSecureFlag') &&
            $collection->getSecureFlag()
            && ($rule = $collection->getSecureRule())
            && ($this->ruleManagement->hasRule($rule))) {
            $rule = $this->ruleManagement->getRule($rule);
            $this->maskCustomerSensitive->applyRule($collection, $rule);
            return;
        }

        // special class ,case virtual type
        if ($collection instanceof \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
            && in_array($collection->getMainTable(), $specialTables)) {
            $rule = $this->ruleManagement->getRule('invoice');
            $this->maskCustomerSensitive->applyRule($collection, $rule);
            return;
        }
        if (!in_array($class, array_keys($sortedCollections))) {
            return;
        }
        $rule = $this->ruleManagement->getRule($sortedCollections[$class]);
        $this->maskCustomerSensitive->applyRule($collection, $rule);
    }
}
