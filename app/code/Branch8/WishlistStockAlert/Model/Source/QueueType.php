<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       02/02/2026
 */

namespace Branch8\WishlistStockAlert\Model\Source;

use Branch8\WishlistStockAlert\Model\Config\ProcessQueueType;
use Magento\Framework\Data\OptionSourceInterface;

class QueueType implements OptionSourceInterface
{
    private $options;

    /**
     * @return array|array[]
     */
    public function toOptionArray()
    {
        if ($this->options) {
            return $this->options;
        }
        $this->options = [
            ['value' => '', 'label' => __('Please Select')],
            ['value' => ProcessQueueType::CRON, 'label' => __('Mysql')],
            ['value' => ProcessQueueType::RABITTMQ, 'label' => __('RabitMQ')],
        ];
        return $this->options;
    }
}
