<?php
declare (strict_types = 1);

namespace Branch8\Refund\Model\SalesRefund;

class Status
{
    const PENDING = 0;
    const TRIGGERED = 1;
    const REFUND_COMPLETE = 2;
    const CANNOT_TRIGGERED = 3;
    const REFUND_FAILED = 4;
    const CANNOT_REFUND = 99; /** Fortest CheckMo or retry times > 10 --> alwaus failed */
}
