<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model\Ticket;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Priority Class
 */
class Priority implements OptionSourceInterface
{
    const LOW = 1;

    const MEDIUM = 2;
    const HIGH = 3;
    const URGENT = 4;

    /**
     * toOptionArray
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            ['label' => __('Low'), 'value' => self::LOW],
            ['label' => __('Medium'), 'value' => self::MEDIUM],
            ['label' => __('High'), 'value' => self::HIGH],
            ['label' => __('Urgent'), 'value' => self::URGENT],
        ];
    }

    /**
     * @return array
     */
    public function toOptions()
    {
        return [
            self::LOW => __('Low'),
            self::MEDIUM => __('Medium'),
            self::HIGH => __('High'),
            self::URGENT => __('Urgent'),
        ];
    }

    /**
     * @param $priority
     * @return mixed|string
     */
    public function getPriorityLabel($priority)
    {
        $options = $this->toOptions();
        return isset($options[$priority]) ? $options[$priority]->render() : '';
    }
}
