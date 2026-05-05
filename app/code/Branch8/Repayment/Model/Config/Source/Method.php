<?php

namespace Branch8\Repayment\Model\Config\Source;

class Method implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Return options array
     *
     * @return array
     */
    public function toOptionArray()
    {

        $options = [
            ['value' => 'hotaipay', 'label' => __('Hotai Pay')]
        ];

        return $options;
    }
}
