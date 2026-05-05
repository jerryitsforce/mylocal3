<?php
namespace Branch8\Mmegamenu\Model\Category;

class DataProvider extends \Magento\Catalog\Model\Category\DataProvider
{
    /**
     * @return array
     */
    protected function getFieldsMap()
    {
        $fields = parent::getFieldsMap();
        $fields['content'][] = 'branch8_megamenu_item_logo';
        return $fields;
    }

}
