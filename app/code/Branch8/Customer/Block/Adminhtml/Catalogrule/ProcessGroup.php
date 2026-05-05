<?php

namespace Branch8\Customer\Block\Adminhtml\Catalogrule;

class ProcessGroup extends \Magento\Backend\Block\Template{

    protected $_template = 'catalogrule/process_group.phtml';
    /**
     * @var \Magento\Customer\Model\ResourceModel\Group\CollectionFactory
     */
    protected $groupCollectionFactory;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;
    /**
     * @var \Magento\Customer\Model\GroupFactory
     */
    protected $customerFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $groupCollectionFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Customer\Model\GroupFactory $customerFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $groupCollectionFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\GroupFactory $customerFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->groupCollectionFactory = $groupCollectionFactory;
        $this->_coreRegistry = $registry;
        $this->customerFactory = $customerFactory;
    }

    /**
     * @return mixed|null
     */
    public function getRuleData(){
        $model = $this->_coreRegistry->registry('current_promo_catalog_rule');
        return $model;
    }

    /**
     * @return array|int|mixed|null
     */
    public function getRuleOrganization(){
        $groupIds = $this->getRuleData()->getData('customer_group_ids');
        $organization = 0;
        if(isset($groupIds[0])){
            $group = $this->customerFactory->create()->load($groupIds[0]);
            $organization = $group->getData('organization');
        }
        return $organization;
    }

    /**
     * @return false|string
     */
    public function getRuleGroups(){
        $groupIds = $this->getRuleData()->getData('customer_group_ids');
        if(!$groupIds){
            $groupIds = [];
        }
        return json_encode($groupIds);
    }

    /**
     * @return false|string
     */
    public function getAllGroups(){
        $groupsCollection = $this->groupCollectionFactory->create();
        $groups = [];
        foreach ($groupsCollection as $_group) {
            $groups[$_group->getCustomerGroupId()] = [
                'value' => $_group->getCustomerGroupCode(),
                'prev_level' => $_group->getPrevLevel(),
                'nxt_level' => $_group->getNxtLevel(),
                'organization' => $_group->getOrganization()
            ];
        }

        return json_encode($groups);
    }
}