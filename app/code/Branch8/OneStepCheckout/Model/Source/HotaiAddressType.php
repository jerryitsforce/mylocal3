<?php

namespace Branch8\OneStepCheckout\Model\Source;

class HotaiAddressType extends \Magento\Eav\Model\Entity\Attribute\Source\Table
{

    const ADDRESS_TYPE_NORMAL = 'normal';
    const ADDRESS_TYPE_CONVENIENCE_STORE = 'convenience_store';

    /**
     * Retrieve option array
     * @return array
     */
    public static function getOptionArray()
    {
        return [
            self::ADDRESS_TYPE_NORMAL => __('Normal'),
            self::ADDRESS_TYPE_CONVENIENCE_STORE => __('Convenience Store'),
        ];
    }
    
    /**
     * @inheritdoc
     */
    public function getAllOptions($withEmpty = true, $defaultValues = false)
    {
        $result = [];
        foreach (self::getOptionArray() as $index => $value) {
            $result[] = ['value' => $index, 'label' => $value];
        }
        return $result;
    }

    /**
     * Retrieve option text
     *
     * @param int $optionId
     * @return string
     */
    public function getOptionText($optionId)
    {
        $options = self::getOptionArray();
        return $options[$optionId] ?? null;
    }
}
