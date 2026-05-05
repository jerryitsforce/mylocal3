<?php

namespace Branch8\Yoxi\Model\Product;

use Magento\Framework\Data\OptionSourceInterface;

class VirtualProductType extends \Magento\Framework\DataObject implements OptionSourceInterface
{
    const ATTRIBUTE_CODE = "virtual_product_type";

    const TYPE_DEFAULT     = 1; // 預設
    const TYPE_YOXI_TICKET = 2; // YOXI票券

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
            self::TYPE_DEFAULT     => __('Default'),
            self::TYPE_YOXI_TICKET => __('YOXI Ticket'),
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
