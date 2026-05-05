<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Customer\Model\Customer\Attribute\Source;

class CarOwner extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{

    /**
     * getAllOptions
     *
     * @return array
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                ['value' => (string) '1', 'label' => __('我是Toyota車主')],
                ['value' => (string) '2', 'label' => __('我是Lexus車主')],
                ['value' => (string) '3', 'label' => __('我不是車主')]
            ];
        }
        return $this->_options;
    }
}

