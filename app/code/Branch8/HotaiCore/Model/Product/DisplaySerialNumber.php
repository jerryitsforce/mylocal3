<?php

namespace Branch8\HotaiCore\Model\Product;

class DisplaySerialNumber extends \Magento\Framework\DataObject implements \Magento\Framework\Data\OptionSourceInterface
{
    const ATTRIBUTE_CODE = "display_serial_number";

    const TYPE_NO_DISPLAY = 0;
    const TYPE_DISPLAY    = 1;

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
            self::TYPE_NO_DISPLAY => __('Disable'),
            self::TYPE_DISPLAY  => __('Enable'),
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
