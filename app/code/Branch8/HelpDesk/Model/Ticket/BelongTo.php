<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model\Ticket;
use Magento\Framework\Data\OptionSourceInterface;
/**
 * Class Belong To
 */
class BelongTo implements OptionSourceInterface
{
    const USER = 1;

    const CUSTOMER = 2;
    const ANOMYNOUS = 0;

    /**
     * toOptionArray
     * Return options
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            ['label' => __('User'), 'value' => self::USER],
            ['label' => __('Customer'), 'value' => self::CUSTOMER],
            ['label' => __('Anomynus'), 'value' => self::ANOMYNOUS]
        ];
    }
}
