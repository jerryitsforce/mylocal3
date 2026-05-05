<?php
declare(strict_types=1);

namespace Branch8\RmaAdminUi\Ui\Component\Listing\Column\RmaOrderStatus;

use Webkul\MpRmaSystem\Helper\Data;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Options for Listing Column Status
 */
class Options implements OptionSourceInterface
{
    private $options;

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        if ($this->options !== null) {
            return $this->options;
        }
        $availableOptions = [
            'arrived' => __("Arrived"),
            'canceled' => __("Canceled"),
            'cancel_pending' => __("Cancellation Pending"),
            'picked' => __("Picked"),
            'processing' => __("Processing"),
            'tallying' => __("Tallying"),
            'pending_payment' => __("Picked"),
        ];
        $this->options = [];
        foreach ($availableOptions as $key => $value) {
            $this->options[] = [
                'label' => $value,
                'value' => $key,
            ];
        }
        return $this->options;
    }
}
