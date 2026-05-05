<?php
namespace Branch8\SellerContactInformation\Model\Config\Source;

class ContractStatus extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    const STATUS_PENDING = 0;

    const STATUS_ACTIVE = 1;

    const STATUS_EXPIRED = 2;

    public function getAllOptions()
    {
        return [
            ['label' => __('Pending'), 'value' => self::STATUS_PENDING],
            ['label' => __('Active'), 'value' => self::STATUS_ACTIVE],
            ['label' => __('Expired'), 'value' => self::STATUS_EXPIRED]
        ];
    }

    public function toArray(){
        return [
            self::STATUS_PENDING => __('Pending'),
            self::STATUS_ACTIVE => __('Active'),
            self::STATUS_EXPIRED => __('Expired')
        ];
    }
}
