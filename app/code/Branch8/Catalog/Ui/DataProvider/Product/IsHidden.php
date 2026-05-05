<?php

namespace Branch8\Catalog\Ui\DataProvider\Product;

class IsHidden implements \Magento\Ui\DataProvider\AddFieldToCollectionInterface{
    /**
     * @param \Magento\Framework\Data\Collection $collection
     * @param $field
     * @param $alias
     * @return void
     */
    public function addField(
        \Magento\Framework\Data\Collection $collection,
                                           $field,
                                           $alias = null
    ){
        $collection->addFieldToSelect('is_hidden');
    }

}