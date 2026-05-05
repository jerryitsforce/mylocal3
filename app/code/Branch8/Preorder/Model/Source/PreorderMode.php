<?php

namespace Branch8\Preorder\Model\Source;

class PreorderMode extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource{
    const START_END_DATE = 1;

    const X_DAYS = 2;

    const SPECIFY_SHIPPING_DATE = 3;

    /**
     * @return array|array[]|null
     */

    public function getAllOptions(){
        if ($this->_options === null) {
            $this->_options = [
                ['label' => __('Start/ End date'), 'value' => self::START_END_DATE],
                ['label' => __('X Days after ordering'), 'value' => self::X_DAYS],
                ['label' => __('Specify Shipping Date'), 'value' => self::SPECIFY_SHIPPING_DATE],
            ];
        }
        return $this->_options;
    }
}