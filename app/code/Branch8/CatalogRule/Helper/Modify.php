<?php
namespace Branch8\CatalogRule\Helper;

class Modify extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $approvalFactory;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Branch8\CatalogRule\Model\ResourceModel\CatalogRuleApproval\CollectionFactory $approvalFactory
    )
    {
        $this->approvalFactory = $approvalFactory;
        parent::__construct($context);
    }

    public function canEditCatalogRule($ruleId){
        $col = $this->approvalFactory->create()
            ->addFieldToFilter('catalogrule_id', $ruleId)
            ->addFieldToFilter('status', \Branch8\CatalogRule\Model\Config\Source\ApproveStatus::STATUS_UNDER_REVIEW);
        if($col->getSize()){
            return false;
        }
        return true;
    }
}
