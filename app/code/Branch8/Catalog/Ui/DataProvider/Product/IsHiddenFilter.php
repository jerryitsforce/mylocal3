<?php

namespace Branch8\Catalog\Ui\DataProvider\Product;

class IsHiddenFilter implements \Magento\Ui\DataProvider\AddFilterToCollectionInterface{
    /**
     * @param \Magento\Framework\Data\Collection $collection
     * @param $field
     * @param $condition
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addFilter(
        \Magento\Framework\Data\Collection $collection,
                                           $field,
                                           $condition = null
    )
    {
        if (isset($condition['eq'])) {
            /**
             * If select filter Hidden is Hidden or Not Hidden, filter eq value
             */
            if(
                $condition['eq'] == \Branch8\Catalog\Model\Source\HiddenTypeFull::NOT_HIDDEN
            || $condition['eq'] == \Branch8\Catalog\Model\Source\HiddenTypeFull::HIDDEN
            ) {
                $collection->addFieldToFilter($field, $condition);
            }
            /**
             * If select Is Hidden: All, do no thing(as the same filter all)
             */
        }else{
            /**
             * If first time go to listing page or no select Is Hidden, always filter NOT HIDDEN
             */
            $ifEmtyCondition = ['eq' => \Branch8\Catalog\Model\Source\HiddenTypeFull::NOT_HIDDEN];
            $collection->addFieldToFilter($field, $ifEmtyCondition);
        }

    }

}