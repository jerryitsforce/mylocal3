<?php
namespace Branch8\GA4\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;

class ClearEvent extends Action
{
    protected $jsonFactory;
    protected $checkoutSession;
    protected $customerSession;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        try {
            $eventType = $this->getRequest()->getParam('event_type');

            switch ($eventType) {
                case 'add_to_cart':
                    $this->checkoutSession->setGA4AddToCartData(null);
                    $this->checkoutSession->unsGA4AddToCartData();
                    break;
                case 'remove_from_cart':
                    $this->customerSession->setGA4RemoveFromCartData(null);
                    $this->customerSession->unsGA4RemoveFromCartData();
                    break;
                case 'checkout_options':
                    $this->checkoutSession->setGA4CheckoutOptionsData(null);
                    $this->checkoutSession->unsGA4CheckoutOptionsData();
                    break;
                case 'add_to_wishlist':
                    $this->customerSession->setGA4AddToWishListData(null);
                    $this->customerSession->unsGA4AddToWishListData();
                    break;
                case 'register':
                    $this->customerSession->setGA4RegisterData(null);
                    break;
                case 'login':
                    $this->customerSession->setGA4LoginData(null);
                    break;
                case 'add_to_compare':
                    $this->customerSession->setGA4AddToCompareData(null);
                    break;
                case 'refund':
                    $this->customerSession->setGA4RefundEventData(null);
                    break;
            }

            return $result->setData(['success' => true]);
        } catch (\Exception $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
