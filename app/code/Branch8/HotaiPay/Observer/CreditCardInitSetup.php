<?php

/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\HotaiPay\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Branch8\HotaiPay\Model\CreditCard\Session;


class CreditCardInitSetup extends AbstractDataAssignObserver
{
    public $creditCardSession;

    /**
     * __construct
     *
     * @return void
     */
    public function __construct(
        Session $creditCardSession
    ) {
        $this->creditCardSession = $creditCardSession;
    }

    /**
     * execute
     *
     * @param  mixed $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $params = $observer->getRequest()->getParams();
        if ($params) {
            if (isset($params['StatusDesc']) && strtoupper($params['StatusDesc']) == 'SUCCESS') {
                $this->creditCardSession->requestNewCardListAndReset();
            }
        }
    }
}
