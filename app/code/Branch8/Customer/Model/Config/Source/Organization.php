<?php

namespace Branch8\Customer\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Organization implements OptionSourceInterface {
    /**
     * @var \Branch8\Customer\Model\ResourceModel\Organization\CollectionFactory
     */
    protected $organizationCollectionFactory;

    /**
     * @param \Branch8\Customer\Model\ResourceModel\Organization\CollectionFactory $organizationCollectionFactory
     */
    public function __construct(
        \Branch8\Customer\Model\ResourceModel\Organization\CollectionFactory $organizationCollectionFactory
    ){
        $this->organizationCollectionFactory = $organizationCollectionFactory;
    }

    /**
     * @return array
     */
    public function toOptionArray(){
        $collection = $this->organizationCollectionFactory->create()
            ->addFieldToSelect('*');
        $collection->getSelect()->order('sort_order asc');
        $organizations[] = ['label' => '------ Please select ------', 'value' => 0];
        foreach ($collection as $_org){
            $organizations[] = ['label' => $_org->getName(), 'value' => $_org->getId()];
        }
        return $organizations;
    }
}