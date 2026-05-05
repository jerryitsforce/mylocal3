<?php

namespace Branch8\HotaiCore\Model\Product;

use Magento\Framework\Data\OptionSourceInterface;

class VirtualProductType extends \Magento\Framework\DataObject implements OptionSourceInterface
{
    const ATTRIBUTE_CODE = "virtual_product_type";

    // 增加TYPE後記得確認看看Helper
    const TYPE_DEFAULT                   = 1; // 預設
    const TYPE_YOXI_TICKET               = 2; // YOXI票券
    const TYPE_EDENRED_TICKET            = 3; // 宜睿票券
    const TYPE_FAMILY_BONUS_PIN_TICKET   = 4; // 全家紅利PIN票券
    const TYPE_GENERAL_NOTIFY_TICKET     = 5; // 通用型核銷票券
    const TYPE_GENERAL_NON_NOTIFY_TICKET = 6; // 通用型非核銷票券
    const TYPE_QWARE_TICKET              = 7; // 安源票券
    const TYPE_OPENHUB_TICKET            = 8; // OpenHub票券

    const TYPES_TICKET              = [2, 3, 4, 5, 6, 7, 8];
    const TYPES_BATCH_IMPORT_TICKET = [2, 4, 5, 6];

    /** @var \Magento\Eav\Model\ResourceModel\Entity\Attribute */
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
            self::TYPE_DEFAULT                   => __('Default'),
            self::TYPE_YOXI_TICKET               => __('YOXI Ticket'),
            self::TYPE_EDENRED_TICKET            => __('Edenred Ticket'),
            self::TYPE_FAMILY_BONUS_PIN_TICKET   => __('Family Bonus PIN'),
            self::TYPE_GENERAL_NOTIFY_TICKET     => __('General Notify Ticket'),
            self::TYPE_GENERAL_NON_NOTIFY_TICKET => __('General Non-Notify Ticket'),
            self::TYPE_QWARE_TICKET              => __('7ELEVEN Ticket'),
            self::TYPE_OPENHUB_TICKET            => __('OpenHub Ticket'),
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
