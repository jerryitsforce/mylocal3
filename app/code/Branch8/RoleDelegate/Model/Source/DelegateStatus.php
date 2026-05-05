<?php

declare(strict_types=1);

namespace Branch8\RoleDelegate\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
class DelegateStatus implements OptionSourceInterface
{
    const PENDING = 'pending';
    const ACTIVE = 'active';
    const EXPIRED = 'expired';
    const CANCELLED = 'cancelled';

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['label' => __('Pending'), 'value' => self::PENDING],
            ['label' => __('Active'), 'value' => self::ACTIVE],
            ['label' => __('Expired'), 'value' => self::EXPIRED],
            ['label' => __('Cancelled'), 'value' => self::CANCELLED],
        ];
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            self::PENDING => __('Pending'),
            self::ACTIVE => __('Active'),
            self::EXPIRED => __('Expired'),
            self::CANCELLED => __('Cancelled'),
        ];
    }
}
