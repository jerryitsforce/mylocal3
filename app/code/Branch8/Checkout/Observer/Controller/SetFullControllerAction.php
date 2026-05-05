<?php
declare(strict_types=1);

namespace Branch8\Checkout\Observer\Controller;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadataFactory;
use Branch8\Checkout\Observer\Controller\PredispatchCheckoutCart;

class SetFullControllerAction implements ObserverInterface
{
    /**
     * @var CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var PublicCookieMetadataFactory
     */
    protected $cookieMetadataFactory;

    /**
     * @param CookieManagerInterface $cookieManager
     * @param PublicCookieMetadataFactory $cookieMetadataFactory
     */
    public function __construct(
        CookieManagerInterface $cookieManager,
        PublicCookieMetadataFactory $cookieMetadataFactory
    ) {
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Framework\App\Request\Http $request */
        $request = $observer->getEvent()->getRequest();
        $fullActionName = $request->getFullActionName();

        $metadata = $this->cookieMetadataFactory->create()
            ->setDuration(31536000) // 1 year
            ->setPath('/')
            ->setHttpOnly(true)
            ->setSecure($request->isSecure());

        $this->cookieManager->setPublicCookie(
            PredispatchCheckoutCart::FULL_CONTROLLER_ACTION,
            $fullActionName,
            $metadata
        );
    }
}
