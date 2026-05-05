<?php
declare(strict_types=1);

namespace Branch8\AppSettings\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Page\Config;
use Magento\Framework\Event\Observer;
use Branch8\HotaiCore\Model\Detection\MobileDetect;

class AddAppDebuggerClassToBody implements ObserverInterface
{

    /**
     * @param Config $config
     */
    public function __construct(
        private readonly Config       $config,
        private readonly MobileDetect $mobileDetect,
        private readonly \Branch8\AppSettings\Helper\Data $helper,
    )
    {
    }

    /**
     *  Add Mobile Class to Body tag
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if ($this->mobileDetect->isHotaiApp() && $this->helper->isDebuggerEnabled()) {
            $this->config->addBodyClass('hotai-app-debugger');
        }
        return $this;
    }
}
