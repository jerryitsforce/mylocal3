<?php

namespace Branch8\HotaiCore\Helper;

use Branch8\HotaiCore\Model\Order\Status as HotaiOrderStatus;

class Status
{
    public function __construct(
    ) {
    }

    public function checkFlowStatusSequenceForChange($oldFlowStatus, $newFlowStatus, $order, $writeComment = true, $commentPrefix = null): bool
    {
        $commentPrefix = ($commentPrefix) ? "{$commentPrefix} | " : "";

        $oldSequence = array_search($oldFlowStatus, HotaiOrderStatus::FORMAL_FLOW);
        $newSequence = array_search($newFlowStatus, HotaiOrderStatus::FORMAL_FLOW);

        if ($oldSequence === false) {
            if ($writeComment) {
                $message = "Old flow status {$oldFlowStatus} is not in FORMAL_FLOW, pass flow status change check.";
                $order->addStatusHistoryComment($commentPrefix . $message)->save();
            }

            return true;
        }

        if ($newSequence === false) {
            if ($writeComment) {
                $message = "New flow status {$newFlowStatus} is not in FORMAL_FLOW, pass flow status change check.";
                $order->addStatusHistoryComment($commentPrefix . $message)->save();
            }

            return true;
        }

        if ($newSequence >= $oldSequence) {
            if ($writeComment) {
                $message = "New flow status({$newSequence}) sequence is greater or equal to old flow status({$oldSequence}), pass flow status change check.";
                $order->addStatusHistoryComment($commentPrefix . $message)->save();
            }

            return true;
        }

        if ($writeComment) {
            $message = "Flow status change from {$oldFlowStatus} to {$newFlowStatus} is not allowed.";
            $order->addStatusHistoryComment($commentPrefix . $message)->save();
        }

        return false;
    }
}
