<?php

namespace Branch8\Spin2Win\Model\Config\Source;

class FloatButtonPage extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    const UNLIMIT_PAGE = 0;

    const HOME_PAGE = 1;

    const HOME_AND_SPECIFY_PAGE = 2;

    public function getAllOptions()
    {
        return [
            ['label' => __('Unlimited'), 'value' => self::UNLIMIT_PAGE],
            ['label' => __('Homepage'), 'value' => self::HOME_PAGE],
            ['label' => __('Home page and Specified page'), 'value' => self::HOME_AND_SPECIFY_PAGE]
        ];
    }
}