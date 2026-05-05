<?php
declare(strict_types=1);

namespace Branch8\HotaiCore\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Page\Config;
use Magento\Framework\Event\Observer;
use Branch8\HotaiCore\Model\Detection\MobileDetect;

class AddMobileClassToBody implements ObserverInterface
{
    public const MOBILE_DEVICE = 'mobile-device';

    /**
     * @param Config $config
     */
    public function __construct(
        private readonly Config       $config,
        private readonly MobileDetect $mobileDetect,
    )
    {
    }

    /**
     *  Add Mobile Class to Body tag
     *
     * @param Observer $observer
     * @return AddMobileClassToBody
     */
    public function execute(Observer $observer): AddMobileClassToBody
    {
        if ($this->mobileDetect->isMobile())
            $this->config->addBodyClass(self::MOBILE_DEVICE);


        if ($this->mobileDetect->isHotaiApp())
            $this->config->addBodyClass('hotai-app');

        return $this;
    }
}
