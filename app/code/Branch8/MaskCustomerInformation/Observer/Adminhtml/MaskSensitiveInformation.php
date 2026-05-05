<?php

namespace Branch8\MaskCustomerInformation\Observer\Adminhtml;

use Branch8\MaskCustomerInformation\Model\AdminPermission;
use Branch8\MaskCustomerInformation\Model\Customer\RuleConfig;
use Branch8\MaskInformation\Model\MaskRulesComposite;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class MaskSensitiveInformation implements ObserverInterface
{
    private MaskRulesComposite $maskRuleComposite;

    private RuleConfig $ruleConfig;
    private AdminPermission $permission;

    /**
     * @param AdminPermission $permission
     * @param MaskRulesComposite $maskRulesComposite
     * @param RuleConfig $ruleConfig
     */
    public function __construct(
        AdminPermission    $permission,
        MaskRulesComposite $maskRulesComposite,
        RuleConfig         $ruleConfig
    )
    {
        $this->permission = $permission;
        $this->ruleConfig = $ruleConfig;
        $this->maskRuleComposite = $maskRulesComposite;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if ($this->permission->canView()) {
            return;
        }
        /**
         * @var $address \Magento\Sales\Model\Order\Address
         */
        $address = $observer->getAddress();
        $config = $this->ruleConfig->getRules();
        if ($address) {
            $rawData = $address->getData();
            foreach ($rawData as $key => $value) {
                if (isset($config[$key]) && $value) {
                    $address->setDataChanges(true);
                    $mask = $this->maskRuleComposite->mask($config[$key], $value);
                    $address->setData($key, $mask);
                }
            }

        }
    }
}
