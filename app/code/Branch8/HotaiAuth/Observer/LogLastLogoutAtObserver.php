<?php

namespace Branch8\HotaiAuth\Observer;

use Branch8\HotaiCore\Model\Detection\MobileDetect;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class LogLastLogoutAtObserver implements ObserverInterface
{
    protected $customLogger;
    private MobileDetect $mobileDetect;

    public function __construct(
        \Branch8\HotaiAuth\Model\CustomLogger $customLogger,
        MobileDetect $mobileDetect
    ) {
        $this->customLogger = $customLogger;
        $this->mobileDetect = $mobileDetect;
    }

    public function execute(Observer $observer)
    {
        $this->customLogger->log(
            $observer->getEvent()->getCustomer()->getId(),
            [
                'last_logout_at' => (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT),
                'device' => $this->mobileDetect->getDeviceType()
            ]
        );
    }
}
