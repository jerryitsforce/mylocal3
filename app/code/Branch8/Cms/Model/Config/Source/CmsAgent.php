<?php
namespace Branch8\Cms\Model\Config\Source;

class CmsAgent implements \Magento\Framework\Data\OptionSourceInterface{

    const TYPE_UNRETRICTED = 0;

    const TYPE_WEB = 1;

    const TYPE_APP = 2;

    public function toOptionArray(){
        return [
            ['value' => self::TYPE_UNRETRICTED, 'label' => __('Unrestricted')], 
            ['value' => self::TYPE_WEB, 'label' => __('Web only')],
            ['value' => self::TYPE_APP, 'label' => __('App only')]
        ];
    }

}
