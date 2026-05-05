<?php

namespace Branch8\Catalog\Model\Source;

class HiddenType extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource{

    const All = 3;

    const NOT_HIDDEN = 1;

    const HIDDEN = 2;

    /**
     * @return array|array[]|null
     */

    public function getAllOptions(){
        if ($this->_options === null) {
            $this->_options = [
                ['label' => __('Not Hidden'), 'value' => self::NOT_HIDDEN],
                ['label' => __('Hidden'), 'value' => self::HIDDEN],
            ];
        }
        return $this->_options;
    }

    /**
     * @return array
     */
    public static function getOptionArray()
    {
        return [self::NOT_HIDDEN => __('Not Hidden'), self::HIDDEN => __('Hidden')];
    }
}