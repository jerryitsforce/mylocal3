<?php

namespace Branch8\Customer\Override\Model\Customer\Attribute\Source;

use Magento\Customer\Api\GroupManagementInterface;

class Group extends \Magento\Customer\Model\Customer\Attribute\Source\Group{

    /**
     * @var \Magento\Customer\Model\ResourceModel\Group\CollectionFactory
     */
    protected $groupCollectionFactory;

    /**
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $attrOptionCollectionFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\OptionFactory $attrOptionFactory
     * @param GroupManagementInterface $groupManagement
     * @param \Magento\Framework\Convert\DataObject $converter
     * @param \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $groupCollectionFactory
     */
    public function __construct(
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $attrOptionCollectionFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\OptionFactory $attrOptionFactory,
        GroupManagementInterface $groupManagement,
        \Magento\Framework\Convert\DataObject $converter,
        \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $groupCollectionFactory
    ){
        parent::__construct($attrOptionCollectionFactory, $attrOptionFactory, $groupManagement, $converter);
        $this->groupCollectionFactory = $groupCollectionFactory;
    }
    public function getAllOptions($withEmpty = true, $defaultValues = false){
        $options = parent::getAllOptions($withEmpty, $defaultValues);
        $ids = [];
        foreach ($options as $_option) {
            $ids[] = $_option['value'];
        }
        $groups = $this->groupCollectionFactory->create()
            ->addFieldToFilter('customer_group_id', ['in' => $ids])
            ->addFieldToSelect('*');
        $newOptions = [];
        foreach ($groups as $_group){
            $newOptions[] = [
                'value' => $_group->getCustomerGroupId(),
                'label' => $_group->getFullname()
            ];
        }
        return $newOptions;
    }
}