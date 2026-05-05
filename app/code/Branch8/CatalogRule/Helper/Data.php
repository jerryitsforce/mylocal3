<?php
namespace Branch8\CatalogRule\Helper;


class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CATALOG_RULE_APPROVAL_ENABLE = 'marketplace/branch8_catalogrule_approval/enable';

    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    )
    {
        parent::__construct($context);
    }

    public function isApprovalEnable(){
        return $this->scopeConfig->getValue(self::CATALOG_RULE_APPROVAL_ENABLE);
    }
}
