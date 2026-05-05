<?php
namespace Branch8\GA4\CustomerData;

use Magento\Customer\CustomerData\SectionSourceInterface;

/**
 * Gtm section
 */
class Ga4 extends \Magento\Framework\DataObject implements SectionSourceInterface
{

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * Constructor
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Checkout\Model\Session $_checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Checkout\Model\Session $_checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
        array $data = []
    )
    {
        parent::__construct($data);
        $this->jsonHelper = $jsonHelper;
        $this->_checkoutSession = $_checkoutSession;
        $this->customerSession = $customerSession;
    }

    /**
     * {@inheritdoc}
     */
    public function getSectionData()
    {
//        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/ga4_events.log');
//        $logger = new \Zend_Log();
//        $logger->addWriter($writer);
        $data = [];

        // Log initial state
//        $logger->info('===== Start GA4 Section Data =====');
//        $logger->info('Session ID: ' . $this->_checkoutSession->getSessionId());
        /** AddToCart data verifications */
        $addToCartData = $this->_checkoutSession->getGA4AddToCartData();
//        $logger->info('AddToCart Data before process: ' . print_r($addToCartData, true));
        if ($addToCartData) {
            $data[] = $addToCartData;
        }

        $this->_checkoutSession->setGA4AddToCartData(null);
        $this->_checkoutSession->unsGA4AddToCartData();
//        $logger->info('AddToCart Data after clear: ' . print_r($this->_checkoutSession->getGA4AddToCartData(), true));

        /** RemoveFromCart data verifications */
        $removeCartData = $this->customerSession->getGA4RemoveFromCartData();
//        $logger->info('RemoveFromCart Data before process: ' . print_r($removeCartData, true));
        if ($removeCartData) {
            $data[] = $removeCartData;
        }

        $this->customerSession->setGA4RemoveFromCartData(null);
        $this->customerSession->unsGA4RemoveFromCartData();
//        $logger->info('RemoveFromCart Data after clear: ' . print_r($this->_checkoutSession->getGA4RemoveFromCartData(), true));

        /** Checkout Steps data verifications */
        $checkoutData = $this->_checkoutSession->getGA4CheckoutOptionsData();

        if ($checkoutData) {
            foreach ($checkoutData as $options) {
                $data[] = $options;
            }
        }

        $this->_checkoutSession->setGA4CheckoutOptionsData(null);
        $this->_checkoutSession->unsGA4CheckoutOptionsData();

        /** Add To Wishlist Data */
        $wishlistData = $this->customerSession->getGA4AddToWishListData();

        if ($wishlistData) {
            $data[] = $wishlistData;
        }

        $this->customerSession->setGA4AddToWishListData(null);
        $this->customerSession->unsGA4AddToWishListData();

        /** Register Data */
        $registerData = $this->customerSession->getGA4RegisterData();

        if ($registerData) {
            $data[] = $registerData;
        }

        $this->customerSession->setGA4RegisterData(null);

        /** Login Data */
        $loginData = $this->customerSession->getGA4LoginData();

        if ($loginData) {
            $data[] = $loginData;
        }

        $this->customerSession->setGA4LoginData(null);

        /** Add To Compare Data */
        $compareData = $this->customerSession->getGA4AddToCompareData();

        if ($compareData) {
            $data[] = $compareData;
        }

        $this->customerSession->setGA4AddToCompareData(null);

        /** Refund Event Data */
        $refundData = $this->customerSession->getGA4RefundEventData();

        if ($refundData) {
            $data[] = $refundData;
        }


        /** Reload Sections Data from Cookie (HttpOnly) */
        $cookieManager = \Magento\Framework\App\ObjectManager::getInstance()->get(\Magento\Framework\Stdlib\CookieManagerInterface::class);
        $cookieMetadataFactory = \Magento\Framework\App\ObjectManager::getInstance()->get(\Magento\Framework\Stdlib\Cookie\CookieMetadataFactory::class);
        $reloadSections = $cookieManager->getCookie('customer_load_sections');
        if ($reloadSections) {
            $metadata = $cookieMetadataFactory->createPublicCookieMetadata()->setPath('/');
            $cookieManager->deleteCookie('customer_load_sections', $metadata);
        }

        return [
            'datalayer' => $this->jsonHelper->jsonEncode($data),
            'reload_sections' => $reloadSections
        ];
    }
}
