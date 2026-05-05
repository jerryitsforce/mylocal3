<?php

declare(strict_types=1);

namespace Branch8\CTBC\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class LogClass implements OptionSourceInterface
{
    /**
     * Provide log class options for admin multiselect.
     *
     * @return array<int, array{value:string,label:string}>
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'OrderStatus', 'label' => 'Command_OrderStatus (var/log/CTBC/OrderStatus/Y_m_d.log)'],
            ['value' => 'Api', 'label' => 'Model_Api (var/log/CTBC/Api/Y_m_d.log)'],
            ['value' => 'OrderManagement', 'label' => 'Model_OrderManagement (var/log/CTBC/OrderManagement/Y_m_d.log)'],
        ];
    }
}

