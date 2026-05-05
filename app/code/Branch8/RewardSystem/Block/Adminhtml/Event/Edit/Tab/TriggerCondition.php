<?php
namespace Branch8\RewardSystem\Block\Adminhtml\Event\Edit\Tab;

use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory as OrderStatusCollectionFactory;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as LevelCollectionFacatory;

class TriggerCondition extends \Magento\Framework\View\Element\Template
{

    protected $triggerConditions;

    protected $orderStatusCollectionFactory;

    protected $levelCollectionFactory;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Branch8\RewardSystem\Model\Config\Source\Conditions $triggerConditions,
        OrderStatusCollectionFactory $orderStatusCollectionFactory,
        LevelCollectionFacatory $levelCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->setTemplate('event/edit/tab/trigger_condition.phtml');
        $this->triggerConditions = $triggerConditions;
        $this->orderStatusCollectionFactory = $orderStatusCollectionFactory;
        $this->levelCollectionFactory = $levelCollectionFactory;
    }

    public function getAllConditions(){
        return $this->triggerConditions->toOptionArray();
    }

    public function getOrderStatusOptions(){
        $options = $this->orderStatusCollectionFactory->create()->toOptionArray();
        return $options;
    }

    public function getCustomerGroupsOptionArray(){
        $collection = $this->levelCollectionFactory->create();
        return $collection->toOptionArray();
    }

}
