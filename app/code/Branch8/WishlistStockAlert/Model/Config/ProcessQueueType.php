<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       27/01/2026
 */

namespace Branch8\WishlistStockAlert\Model\Config;

class ProcessQueueType implements \Magento\Framework\Option\ArrayInterface
{
    const CRON = 'cron';
    const RABITTMQ = 'rabitmq';

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => self::CRON, 'label' => __('Cron')], ['value' => self::RABITTMQ, 'label' => __('RabitMQ')]];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [self::CRON => __('Cron'), self::RABITTMQ => __('RabitMQ')];
    }
}
