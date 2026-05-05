<?php
declare(strict_types=1);

namespace Branch8\MaskCustomerInformation\Observer\Adminhtml;

use Branch8\MaskCustomerInformation\Model\AdminPermission;
use Branch8\MaskCustomerInformation\Model\MaskCustomerSensitive;
use Branch8\MaskCustomerInformation\Model\RuleManagement;
use Psr\Log\LoggerInterface;

class HideSensitiveDefaultObserver extends \Branch8\MaskCustomerInformation\Observer\HideSensitiveDefaultObserver
{
    /**
     * @param AdminPermission $permission
     * @param RuleManagement $ruleManagement
     * @param MaskCustomerSensitive $maskCustomerSensitive
     * @param LoggerInterface $logger
     * @param array $collectionChecks
     * @param array $ignoreClasses
     */
    public function __construct(
        AdminPermission                                              $permission,
        RuleManagement                                               $ruleManagement,
        MaskCustomerSensitive $maskCustomerSensitive,
        LoggerInterface                                              $logger,
        array                                                        $collectionChecks = [],
        array                                                        $ignoreClasses = []
    )
    {
        parent::__construct($permission, $ruleManagement, $maskCustomerSensitive,$logger, $collectionChecks, $ignoreClasses);
    }
}
