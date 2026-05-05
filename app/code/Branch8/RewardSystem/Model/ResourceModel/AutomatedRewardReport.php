<?php
namespace Branch8\RewardSystem\Model\ResourceModel;

class AutomatedRewardReport extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context
    )
    {
        parent::__construct($context);
    }

    protected function _construct()
    {
        $this->_init('branch8_rewardsystem_report', 'entity_id');
    }
}
