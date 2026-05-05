<?php

namespace Branch8\MarketPlaceParentOrderFrontendUi\Plugin\Marketplace\Block\Order;

class TotalsPlugin{

    public function afterGetLabelProperties($subject, $result) {
        $paymentCode = '';
        $payment = $subject->getOrder()->getPayment();
        if ($payment) {
            $paymentCode = $payment->getMethod();
        }
        if ($paymentCode == 'mpcashondelivery') {
            return 'colspan="10" class="mark"';
        }
        return 'colspan="9" class="mark"';
    }

}