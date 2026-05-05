<?php
namespace Branch8\RewardSystem\Block\Adminhtml\Event\Edit\Tab;

class TriggersDetail extends \Magento\Framework\View\Element\Template
{
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->setTemplate('event/edit/tab/triggers_detail.phtml');
    }

    public function getTriggerType(){
        $triggerData = $this->getData('triggerData');
        if(!$triggerData){
            return \Branch8\RewardSystem\Model\Config\Source\TriggerType::TYPE_AND;
        }
        $triggers = json_decode($triggerData, true);
        if(!isset($triggers['triggerType'])){
            return \Branch8\RewardSystem\Model\Config\Source\TriggerType::TYPE_AND;
        }
        return $triggers['triggerType'];
    }

    public function getConditions(){
        $triggerData = $this->getData('triggerData');
        if(!$triggerData){
            return [];
        }
        $triggers = json_decode($triggerData, true);
        if(!isset($triggers['conditions'])){
            return [];
        }
        return $triggers['conditions'];
    }

}
