<?php

namespace Branch8\HotaiCore\Setup\Unused;

use Branch8\HotaiCore\Model\Order\State as HotaiOrderState;
use Branch8\HotaiCore\Model\Order\Status as HotaiOrderStatus;

class Status
{
    const VERSION_1 = [
        'closed_refund_success',
        'pending_payment'
    ];

    const VERSION_2 = [
        'processing_return_cancel_before_',
    ];
}