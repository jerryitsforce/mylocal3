<?php
declare(strict_types=1);

namespace Branch8\MaskCustomerInformation\Plugin\Magento\Backend\Block\Widget\Grid;

use Branch8\MaskCustomerInformation\Model\MaskCustomerSensitive;
use Branch8\MaskCustomerInformation\Model\PermissionInterface;
use Branch8\MaskCustomerInformation\Model\RuleManagement;

class ExtendedPlugin
{
    private PermissionInterface $permission;
    private array $collectionChecks;

    private $sortedCollections = null;
    private MaskCustomerSensitive $maskCustomerSensitive;
    private RuleManagement $ruleManagement;

    /**
     * @param PermissionInterface $permission
     * @param MaskCustomerSensitive $maskCustomerSensitive
     * @param RuleManagement $ruleManagement
     * @param array $collectionChecks
     */
    public function __construct(
        PermissionInterface   $permission,
        MaskCustomerSensitive $maskCustomerSensitive,
        RuleManagement        $ruleManagement,
        array                 $collectionChecks = []
    )
    {
        $this->ruleManagement = $ruleManagement;
        $this->permission = $permission;
        $this->maskCustomerSensitive = $maskCustomerSensitive;
        $this->collectionChecks = $collectionChecks;
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
     * Get collection object
     *
     * @return \Magento\Framework\Data\Collection
     */
    public function afterGetCollection($subject, $collection)
    {
        if ($collection && !$this->permission->canView()) {
            $sortedCollections = $this->getSortedCollections();
            $class = get_class($collection);
            if (isset($sortedCollections[$class]) && $collection->isLoaded()) {
                $this->maskCustomerSensitive->applyRule(
                    $collection,
                    $this->ruleManagement->getRule($sortedCollections[$class])
                );
            }
        }
        return $collection;
    }
}
