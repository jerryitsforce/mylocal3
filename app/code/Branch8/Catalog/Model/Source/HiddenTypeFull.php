<?php

namespace Branch8\Catalog\Model\Source;

class HiddenTypeFull extends \Branch8\Catalog\Model\Source\HiddenType{
    /**
     * @return array|array[]|null
     */

    public function getAllOptions(){
        if ($this->_options === null) {
            $this->_options = [
                ['label' => __('All'), 'value' => self::All],
                ['label' => __('Not Hidden'), 'value' => self::NOT_HIDDEN],
                ['label' => __('Hidden'), 'value' => self::HIDDEN],
            ];
        }
        return $this->_options;
    }

    public static function getOptionArray()
    {
        return [self::All => __('All'), self::NOT_HIDDEN => __('Not Hidden'), self::HIDDEN => __('Hidden')];
    }


}