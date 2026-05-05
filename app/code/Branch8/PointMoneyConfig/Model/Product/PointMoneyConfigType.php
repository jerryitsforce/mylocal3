<?php

namespace Branch8\PointMoneyConfig\Model\Product;

use Magento\Framework\Data\OptionSourceInterface;

class PointMoneyConfigType extends \Magento\Framework\DataObject implements OptionSourceInterface
{
    const TYPE_ONLY_MONEY               = 1; // 純金
    const TYPE_ONLY_POINT               = 2; // 純點
    const TYPE_FREE_RATIO               = 3; // 點金自由配:折抵範圍上/下限
    const TYPE_FIXED_POINT_AND_MONEY    = 4; // 固定點+固定金
    const TYPE_FREE_RATIO_WITHOUT_LIMIT = 5; // 點金自由配:無限制

    /**
     * Eav entity attribute
     *
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute
     */
    protected $_eavEntityAttribute;

    /**
     * Construct
     *
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavEntityAttribute
     * @param array $data
     */
    public function __construct(
        \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavEntityAttribute,
        array $data = []
    ) {
        $this->_eavEntityAttribute = $eavEntityAttribute;
        parent::__construct($data);
    }

    /**
     * Retrieve option array
     *
     * @return array
     * phpcs:disable Magento2.Functions.StaticFunction
     */
    public static function getOptionArray()
    {
        return [
            self::TYPE_ONLY_MONEY               => __('Only money'),
            self::TYPE_ONLY_POINT               => __('Only point'),
            self::TYPE_FREE_RATIO               => __('Free ratio'),
            self::TYPE_FREE_RATIO_WITHOUT_LIMIT => __('Free ratio without limit')
//            self::TYPE_FIXED_POINT_AND_MONEY    => __('Fixed point and money'),
        ];
    }

    /**
     * Retrieve all options
     *
     * @return array
     */
    public static function getAllOption()
    {
        $options = self::getOptionArray();
        array_unshift($options, ['value' => '', 'label' => '']);
        return $options;
    }

    /**
     * Retrieve all options
     *
     * @return array
     */
    public static function getAllOptions()
    {
        $res = [];
        foreach (self::getOptionArray() as $index => $value) {
            $res[] = ['value' => $index, 'label' => $value];
        }
        return $res;
    }

    /**
     * Retrieve option text
     *
     * @param int $optionId
     * @return string
     */
    public static function getOptionText($optionId)
    {
        $options = self::getOptionArray();
        return $options[$optionId] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray()
    {
        return $this->getAllOptions();
    }
}
