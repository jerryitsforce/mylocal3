<?php

namespace Branch8\MarketplaceProduct\Observer;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Catalog\Api\ProductRepositoryInterface;

class TrackProductUser implements ObserverInterface
{
    protected $adminSession;
    protected $productRepository;
    protected RequestInterface $request;


    public function __construct(
        AdminSession $adminSession,
        ProductRepositoryInterface $productRepository,
        RequestInterface $request
    ) {
        $this->adminSession = $adminSession;
        $this->productRepository = $productRepository;
        $this->request = $request;
    }

    public function execute(Observer $observer)
    {
        $controller = $this->request->getRouteName();
        if ($controller == 'marketplacectrl' ) {
            return;
        }

        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getEvent()->getProduct();
        $adminUser = $this->adminSession->getUser();
        $lastUpdatedUser = $product->getData('admin_user_updated');
        if ($adminUser && $adminUser->getUserName() != $lastUpdatedUser) {
            $adminUsername = $adminUser->getUsername();
            // Set the custom attribute value
            $product->setCustomAttribute('admin_user_updated', $adminUsername);
        }
    }
}
