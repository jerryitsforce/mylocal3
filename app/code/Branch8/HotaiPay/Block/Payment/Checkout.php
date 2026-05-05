<?php

namespace Branch8\HotaiPay\Block\Payment;

use Branch8\HotaiPay\Service\HotaiPay;
use \Magento\Checkout\Model\Session;
use \Magento\Framework\View\Element\Template\Context;

class Checkout extends \Magento\Framework\View\Element\Template
{

    public $hotaiPayService;

    /**
     * __construct
     *
     * @return void
     */
    public function __construct(
        HotaiPay $hotaiPayService,
        Context $context,
        Session $checkoutSession,
        array $data = []
    ) {
        $this->hotaiPayService = $hotaiPayService;
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context, $data);
    }

    /**
     * getCheckoutUrl
     *
     * @return void
     */
    public function getCheckoutUrl()
    {
        return $this->hotaiPayService->data->getCheckoutUrl();
    }
}
