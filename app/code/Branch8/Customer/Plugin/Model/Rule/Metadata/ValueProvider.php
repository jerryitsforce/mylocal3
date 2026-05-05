<?php

namespace Branch8\Customer\Plugin\Model\Rule\Metadata;

class ValueProvider{
    /**
     * @var \Magento\Customer\Model\ResourceModel\Group\CollectionFactory
     */
    protected $groupCollectionFactory;

    /**
     * @param \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $groupCollectionFactory
     */
    public function __construct(
        \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $groupCollectionFactory
    ){
        $this->groupCollectionFactory = $groupCollectionFactory;
    }

    public function afterGetMetadataValues($subject, $result, $rule){
        $customerGroup = $result['rule_information']['children']['customer_group_ids']['arguments']['data']['config']['options'];
        foreach ($customerGroup as $_option) {
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
        $result['rule_information']['children']['customer_group_ids']['arguments']['data']['config']['options'] = $newOptions;
        return $result;
    }
}