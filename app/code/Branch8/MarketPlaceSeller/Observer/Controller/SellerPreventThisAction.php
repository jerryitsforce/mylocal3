<?php

namespace Branch8\MarketPlaceSeller\Observer\Controller;

use Magento\Captcha\Observer\CaptchaStringResolver;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\UrlInterface;

class SellerPreventThisAction implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Framework\App\ActionFlag
     */
    protected $_actionFlag;

    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    protected $redirect;

    /**
     * @param ActionFlag $actionFlag
     * @param \Magento\Framework\App\Response\RedirectInterface $redirect
     */
    public function __construct(
        \Magento\Framework\App\ActionFlag $actionFlag,
        \Magento\Framework\App\Response\RedirectInterface $redirect
    ) {
        $this->_actionFlag = $actionFlag;
        $this->redirect = $redirect;
    }

    /**
     * Check Captcha On Forgot Password Page
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Framework\App\Action\Action $controller */
        $controller = $observer->getControllerAction();

        $this->_actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);
        $this->redirect->redirect($controller->getResponse(), '/');


        return $this;
    }

}