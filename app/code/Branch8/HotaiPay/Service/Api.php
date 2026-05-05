<?php 

namespace Branch8\HotaiPay\Service;


use Branch8\HotaiPay\Model\Api\Payment;
use Branch8\HotaiPay\Model\Api\CreditCard;

class Api
{    
    /**
     * __construct
     *
     * @return void
     */
    public function __construct(
        public CreditCard $creditCard,
        public Payment $payment
    ){
        $this->creditCard = $creditCard;
        $this->payment = $payment;
    }

}