<?php

namespace Branch8\HotaiCancelOrder\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class LogOption implements OptionSourceInterface
{
    public const LOG_CLEAN_EXPIRED_ORDERS = 'clean_expired_orders_log';

    /**
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::LOG_CLEAN_EXPIRED_ORDERS,
                'label' => __(
                    'CleanExpiredOrders logs(var/log/hotai_auth/Cron/{Y_m_d}.log)'
                ),
            ],
        ];
    }
}

