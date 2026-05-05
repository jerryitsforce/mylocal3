<?php
declare(strict_types=1);
/**
 * Order Statuses source model
 */

namespace Branch8\MarketPlaceOrderExportRabbitMQ\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Status
 * @api
 * @since 100.0.2
 */
class Status implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'pending', 'label' => __('Pending')],
            ['value' => 'processing', 'label' => __('Pending')],
            ['value' => 'done', 'label' => __('Done')],
        ];
    }
}
