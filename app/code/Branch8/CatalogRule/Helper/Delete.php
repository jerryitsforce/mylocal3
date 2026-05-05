<?php
namespace Branch8\CatalogRule\Helper;

use Branch8\CatalogRule\Model\Config\Source\ApproveStatus;

class Delete extends \Magento\Framework\App\Helper\AbstractHelper
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

    public function disApproveDeleteRule($ruleId){
        $conn = $this->approvalFactory->create()->getResource()->getConnection();
        $sqlUpdate = 'update catalogrule_approval set status='.ApproveStatus::STATUS_REJECTED.', reject_reason="The Rule have been deleted"
            where catalogrule_id='.$ruleId;
        $conn->query($sqlUpdate);
    }
}
