<?php

namespace Branch8\Catalog\Model\Source;

class CostSetting extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource{

    const FIXED = 1;

    const MANUALLY = 0;

    /**
     * @return array|array[]|null
     */

    public function getAllOptions(){
        if ($this->_options === null) {
            $this->_options = [
                ['label' => __('Fixed commission'), 'value' => self::FIXED],
                ['label' => __('Manually input'), 'value' => self::MANUALLY],
            ];
        }
        return $this->_options;
    }

    /**
     * @return array
     */
    public static function getOptionArray()
    {
        return [self::FIXED => __('Fixed commission'), self::MANUALLY => __('Manually input')];
    }
}