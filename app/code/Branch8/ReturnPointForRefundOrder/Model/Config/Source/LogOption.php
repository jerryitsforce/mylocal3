<?php
declare(strict_types=1);

namespace Branch8\ReturnPointForRefundOrder\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class LogOption implements OptionSourceInterface
{
    public const LOG_RETURN_POINT_CRON = 'return_point_cron';

    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::LOG_RETURN_POINT_CRON,
                'label' => __('Cron_ReturnPoint (var/log/ReturnPointForRefundOrder/Cron/ReturnPoint/{Y_m_d}.log)'),
            ],
        ];
    }
}
