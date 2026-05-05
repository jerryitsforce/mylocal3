<?php

namespace Branch8\HotaiPay\Block\CreditCard;

use Branch8\HotaiPay\Service\HotaiPay;
use \Magento\Framework\View\Element\Template\Context;

class Add extends \Magento\Framework\View\Element\Template
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
        array $data = []
    ) {
        $this->hotaiPayService = $hotaiPayService;
        parent::__construct($context, $data);
    }


    /**
     * getCreditCardManualUrl
     * 
     * @return void | string
     */
    public function getCreditCardManualUrl()
    {
        return $this->hotaiPayService->data->getCreditCardManualUrl();
    }

    /**
     * getCreditCardFastUrl
     *
     * @return void | string
     */
    public function getCreditCardFastUrl()
    {
        return $this->hotaiPayService->data->getCreditCardFastUrl();
    }
}
