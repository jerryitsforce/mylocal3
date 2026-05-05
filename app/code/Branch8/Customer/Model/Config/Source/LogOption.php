<?php
namespace Branch8\Customer\Model\Config\Source;

class LogOption implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray() {
        return [
            ['value' => 'systemlog', 'label' => __('System Log(var/log/system.log)')],
            ['value' => 'exceptionlog', 'label' => __('Exception Log(var/log/exception.log)')],
            ['value' => 'car_owner', 'label' => __('Car Owner Log(var/log/car_owner.log)')],
            ['value' => 'debuglog', 'label' => __('Debug Log(var/log/debug.log)')],
            ['value' => 'recheck_pending_payment', 'label' => __('ReCheck Payment Log(var/log/Sales/recheckPendingPayment/{Y_m_d}.log)')],
        ];
    }
}
