<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Order Statuses source model
 */

namespace Branch8\MarketPlaceSeller\Model\Config\Source;

use Branch8\HotaiCore\Model\Order\State as HotaiOrderState;
use Branch8\HotaiCore\Model\Order\Status as HotaiStatus;
use Branch8\Rma\Model\Rma\Status as RmaStatus;


/**
 * Class Status
 * @api
 * @since 100.0.2
 */
class FlowStatus implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['label'=>'processing', 'value'=> 'processing'],
            ['label'=>'pending_payment', 'value'=> 'pending_payment'],
            ['label'=>'arrived', 'value'=> 'arrived'],
            ['label'=>'returned', 'value'=> 'returned'],
            ['label'=>'return_refund_fail', 'value'=> 'return_refund_fail'],
            ['label'=>'shipping', 'value'=> 'shipping'],
            ['label'=>'tallying', 'value'=> 'tallying'],
            ['label'=>'canceled', 'value'=> 'canceled'],
            ['label'=>'pending', 'value'=> 'pending'],
            ['label'=>'applying_return_review', 'value'=> 'applying_return_review'],
            ['label'=>'processing_replace_arrived', 'value'=> 'processing_replace_arrived'],
            ['label'=>'processing_refund', 'value'=> 'processing_refund'],
            ['label'=>'applying_return_reject', 'value'=> 'applying_return_reject'],
            ['label'=>'applying_return', 'value'=> 'applying_return'],
            ['label'=>'applying_return_cancel', 'value'=> 'applying_return_cancel'],
            ['label'=>'financial_review', 'value'=> 'financial_review'],
            ['label'=>'applying_replace', 'value'=> 'applying_replace'],
            ['label'=>'applying_replace_reject', 'value'=> 'applying_replace_reject'],
            ['label'=>'processing_not_return_but_refund', 'value'=> 'processing_not_return_but_refund']
        ];
    }
}
