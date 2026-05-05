<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\RmaAdminUi\Ui\Component\Listing\Column\ResolutionType;

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
            Data::RESOLUTION_REFUND => __("Rma Refund"),
            Data::RESOLUTION_REPLACE => __("Replace"),
           // Data::RESOLUTION_CANCEL => __("Cancel Items"),

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
