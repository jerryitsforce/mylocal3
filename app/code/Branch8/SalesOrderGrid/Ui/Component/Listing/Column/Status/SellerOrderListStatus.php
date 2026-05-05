<?php

namespace Branch8\SalesOrderGrid\Ui\Component\Listing\Column\Status;

use Branch8\Sales\Ui\Component\Listing\Column\Status\Options as StatusOptions;

/**
 * Class OrderListStatus is used tp get the order list status
 */
class SellerOrderListStatus extends \Branch8\MarketplaceStaging\Model\Product\Source\OrderListStatus
{
    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        $availableOptions = parent::toOptionArray();
        $options = [];
        foreach ($availableOptions as $key => $value) {
            if ($value['disable'])
                continue;
            $options[] = [
                'label' => $value['label'],
                'row_label' => "<span class='wk-mp-grid-status wk-mp-grid-status-" .
                    $value['value'] . "'>" . $value['label'] . "</span>",
                'value' => $value['value'],
                'disable' => $value['disable'] ?? false,
            ];
        }
        return $options;
    }
}
