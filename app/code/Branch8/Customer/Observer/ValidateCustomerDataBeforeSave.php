<?php

declare(strict_types=1);

namespace Branch8\Customer\Observer;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;

class ValidateCustomerDataBeforeSave implements ObserverInterface
{
    /**
     * @inheritDoc
     *
     * @throws LocalizedException
     */
    public function execute(Observer $observer): void
    {
        /** @var RequestInterface $request */
        $request = $observer->getEvent()->getRequest();

        $postValue = $request->getPostValue('customer');
        $invoiceCarrier = $postValue['invoice_carrier'] ?? null;

        if (!empty($invoiceCarrier) && !preg_match('/^\/[0-9A-Z.\-+]{7}$/', $invoiceCarrier)) {
            throw new LocalizedException(
                __('Invoice Carrier: the first character must be "/", followed by 7 characters consisting of numbers (0–9), uppercase letters (A–Z), and special characters (".", "-", "+").')
            );
        }
    }
}
