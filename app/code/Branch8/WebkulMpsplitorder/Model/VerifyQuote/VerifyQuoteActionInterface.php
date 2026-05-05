<?php

namespace Branch8\WebkulMpsplitorder\Model\VerifyQuote;

interface VerifyQuoteActionInterface
{
    /**
     * @param \Magento\Backend\Model\Session\Quote $quoteSession
     * @param \Magento\Sales\Model\AdminOrder\Create $adminOrderModel
     * @return VerifyResult
     */
    public function verify(
        \Magento\Backend\Model\Session\Quote   $quoteSession,
        \Magento\Sales\Model\AdminOrder\Create $adminOrderModel
    );
}
