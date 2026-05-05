<?php

namespace Branch8\Customer\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Group implements OptionSourceInterface {
    /**
     * @var \Branch8\Customer\Model\ResourceModel\Organization\CollectionFactory
     */
    protected $organizationCollectionFactory;

    /**
     * @param \Branch8\Customer\Model\ResourceModel\Organization\CollectionFactory $organizationCollectionFactory
     */
    public function __construct(
        \Magento\Customer\Model\ResourceModel\Group\CollectionFactory $groupCollectionFactory
    ){
        $this->groupCollectionFactory = $groupCollectionFactory;
    }

    /**
     * @return array
     */
    public function toOptionArray(){
        $collection = $this->organizationCollectionFactory->create()
            ->addFieldToSelect('*');
        $collection->getSelect()->order('sort_order asc');
        $organizations = [];
        foreach ($collection as $_org){
            $organizations[] = ['label' => $_org->getName(), 'value' => $_org->getId()];
        }
        return $organizations;
    }
}