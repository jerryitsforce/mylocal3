<?php

namespace Branch8\CustomNotification\Ui\Component\Notification;

use Branch8\CustomNotification\Model\ConfigData;

class Levels implements \Magento\Framework\Option\ArrayInterface
{
    private $options = null;
    /**
     * @var \Magento\Customer\Model\ResourceModel\Group\CollectionFactory
     */
    protected $groupCollectionFactory;

    /**
     * @param \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $groupCollectionFactory
     */
    public function __construct(
        \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $groupCollectionFactory
    )
    {
        $this->groupCollectionFactory = $groupCollectionFactory;
    }

    /**
     * @return array|array[]
     */
    public function toOptionArray()
    {
        if ($this->options) {
            return $this->options;
        }
        $groupCollection = $this->groupCollectionFactory->create()
            ->addFieldToSelect(['customer_group_id', 'customer_group_code', 'organization']);
        $customerLevels = [];

        foreach ($groupCollection as $group) {
            if ($group->getCustomerGroupId() == 0) {
                continue;
            }
            $customerLevels[] = [
                'value' => $group->getCustomerGroupId(),
                'label' => $group->getCode()
            ];
        }

        $this->options = array_map(function ($customer) {
            return ['label' => $customer['label'], 'value' => $customer['value']];
        }, $customerLevels);
        $this->options[] = ['label' => __('指定會員-One ID'), 'value' => ConfigData::ONE_ID_GROUP];
        return $this->options;
    }
}
