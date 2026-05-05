<?php

namespace Branch8\Marketplace\Plugin\Webkul\Marketplace\Observer;


use Magento\Framework\Event\ObserverInterface;
use Magento\UrlRewrite\Model\UrlRewriteFactory;
use Webkul\Marketplace\Helper\Data as MpHelper;
use Webkul\Marketplace\Helper\Email as MpEmailHelper;
use Webkul\Marketplace\Model\SellerFactory as MpSellerFactory;
use Branch8\Marketplace\Service\MarketplaceLogger;
use Magento\Framework\App\ObjectManager;

class CustomerRegisterSuccessObserver implements ObserverInterface
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;

    /**
     * @var MpSellerFactory
     */
    protected $mpSellerFactory;

    /**
     * @var UrlRewriteFactory
     */
    protected $urlRewriteFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlInterface;

    /**
     * @var MpEmailHelper
     */
    protected $mpEmailHelper;

    /**
     * @var \Magento\Backend\Model\Url
     */
    protected $urlBackendModel;
    /**
     * @var MpHelper
     */
    protected $mpHelper;
    private MarketplaceLogger $marketplaceLogger;

    /**
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param MpHelper $mpHelper
     * @param MpSellerFactory $mpSellerFactory
     * @param UrlRewriteFactory $urlRewriteFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\UrlInterface $urlInterface
     * @param MpEmailHelper $mpEmailHelper
     * @param \Magento\Backend\Model\Url $urlBackendModel
     * @param MarketplaceLogger|null $marketplaceLogger
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Store\Model\StoreManagerInterface  $storeManager,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        MpHelper                                    $mpHelper = null,
        MpSellerFactory                             $mpSellerFactory = null,
        UrlRewriteFactory                           $urlRewriteFactory = null,
        \Magento\Customer\Model\Session             $customerSession = null,
        \Magento\Framework\UrlInterface             $urlInterface = null,
        MpEmailHelper                               $mpEmailHelper = null,
        \Magento\Backend\Model\Url                  $urlBackendModel = null,
        MarketplaceLogger                           $marketplaceLogger = null
    )
    {
        $this->_storeManager = $storeManager;
        $this->_messageManager = $messageManager;
        $this->_date = $date;
        $this->mpHelper = $mpHelper ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(MpHelper::class);
        $this->mpSellerFactory = $mpSellerFactory ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(MpSellerFactory::class);
        $this->urlRewriteFactory = $urlRewriteFactory ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(UrlRewriteFactory::class);
        $this->customerSession = $customerSession ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(\Magento\Customer\Model\Session::class);
        $this->urlInterface = $urlInterface ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(\Magento\Framework\UrlInterface::class);
        $this->mpEmailHelper = $mpEmailHelper ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(MpEmailHelper::class);
        $this->urlBackendModel = $urlBackendModel ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(\Magento\Backend\Model\Url::class);
        $this->marketplaceLogger = $marketplaceLogger
            ?: ObjectManager::getInstance()->get(MarketplaceLogger::class);
    }

    /**
     * Customer register event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $data = $observer['account_controller'];
        try {
            $paramData = $data->getRequest()->getParams();
            if (!empty($paramData['is_seller']) && !empty($paramData['profileurl']) && $paramData['is_seller'] == 1) {
                $customer = $observer->getCustomer();

                $profileurlcount = $this->mpSellerFactory->create()->getCollection();
                $profileurlcount->addFieldToFilter(
                    'shop_url',
                    $paramData['profileurl']
                );
                if (!$profileurlcount->getSize()) {
                    $partnerApprovalStatus = $this->mpHelper->getIsPartnerApproval();
                    $status = $partnerApprovalStatus ? 0 : 1;
                    $customerid = $customer->getId();
                    $model = $this->mpSellerFactory->create();
                    $model->setData('is_seller', $status);
                    $model->setData('shop_url', $paramData['profileurl']);
                    $model->setData('seller_id', $customerid);
                    $model->setData('store_id', 0);
                    $model->setCreatedAt($this->_date->gmtDate());
                    $model->setUpdatedAt($this->_date->gmtDate());
                    $model->setAdminNotification(1);
                    $model->save();
                    $loginUrl = $this->urlInterface->getUrl("marketplace/account/dashboard");
                    $this->customerSession->setBeforeAuthUrl($loginUrl);
                    $this->customerSession->setAfterAuthUrl($loginUrl);

                    $helper = $this->mpHelper;
                    if ($helper->getAutomaticUrlRewrite()) {
                        $this->createSellerPublicUrls($paramData['profileurl']);
                    }

                    $adminStoremail = $helper->getAdminEmailId();
                    $adminEmail = $adminStoremail ? $adminStoremail : $helper->getDefaultTransEmailId();
                    $adminUsername = $helper->getAdminName();
                    $receiverInfo = [
                        'name' => $customer->getFirstName() . ' ' . $customer->getLastName(),
                        'email' => $customer->getEmail(),
                    ];
                    $senderInfo = [
                        'name' => $adminUsername,
                        'email' => $adminEmail,
                    ];

                    if ($partnerApprovalStatus) {
                        $emailTemplateVariables['myvar1'] = $customer->getFirstName() . ' ' .
                            $customer->getLastName();
                        $emailTemplateVariables['myvar2'] = $this->urlInterface->getUrl(
                            'marketplace/account/login'
                        );
                        $emailTemplateVariables['myvar3'] = $customer->getFirstName() . ' ' .
                            $customer->getLastName();

                        $this->mpEmailHelper->sendNewSellerRequest(
                            $emailTemplateVariables,
                            $senderInfo,
                            $receiverInfo
                        );
                    }else{
                        //send auto approval email
                        $emailTemplateVariables['seller_name'] = $customer->getFirstName() . ' ' .
                            $customer->getLastName();
                        $emailTemplateVariables['login_url'] = $this->urlInterface->getUrl(
                            'marketplace/account/login'
                        );
                        $this->mpEmailHelper->sendSellerAutoApprovalEmail(
                            $emailTemplateVariables,
                            $senderInfo,
                            $receiverInfo);
                    }
                } else {
                    $this->_messageManager->addError(
                        __('This Shop URL already Exists.')
                    );
                }
            }
        } catch (\Exception $e) {
            $this->marketplaceLogger->logException('CustomerRegisterSuccessObserver', $e);
            $this->_messageManager->addError($e->getMessage());
        }
    }

    /**
     * Create seller urls
     *
     * @param string $profileurl
     */
    private function createSellerPublicUrls($profileurl = '')
    {
        if ($profileurl) {
            $getCurrentStoreId = $this->mpHelper->getCurrentStoreId();

            /*
            * Set Seller Profile Url
            */
            $sourceProfileUrl = 'marketplace/seller/profile/shop/' . $profileurl;
            $requestProfileUrl = $profileurl;
            /*
            * Check if already rexist in url rewrite model
            */
            $urlId = '';
            $profileRequestUrl = '';
            $urlCollectionData = $this->urlRewriteFactory->create()
                ->getCollection()
                ->addFieldToFilter('target_path', $sourceProfileUrl)
                ->addFieldToFilter('store_id', $getCurrentStoreId);
            foreach ($urlCollectionData as $value) {
                $urlId = $value->getId();
                $profileRequestUrl = $value->getRequestPath();
            }
            if ($profileRequestUrl != $requestProfileUrl) {
                $this->urlRewriteFactory->create()
                    ->load($urlId)
                    ->setStoreId($getCurrentStoreId)
                    ->setIsSystem(0)
                    ->setTargetPath($sourceProfileUrl)
                    ->setRequestPath($requestProfileUrl)
                    ->save();
            }

            /*
            * Set Seller Collection Url
            */
            $sourceCollectionUrl = 'marketplace/seller/collection/shop/' . $profileurl;
            $requestCollectionUrl = $profileurl . '/collection';
            /*
            * Check if already rexist in url rewrite model
            */
            $urlId = '';
            $collectionRequestUrl = '';
            $urlCollectionData = $this->urlRewriteFactory->create()
                ->getCollection()
                ->addFieldToFilter('target_path', $sourceCollectionUrl)
                ->addFieldToFilter('store_id', $getCurrentStoreId);
            foreach ($urlCollectionData as $value) {
                $urlId = $value->getId();
                $collectionRequestUrl = $value->getRequestPath();
            }
            if ($collectionRequestUrl != $requestCollectionUrl) {
                $this->urlRewriteFactory->create()->load($urlId)
                    ->setStoreId($getCurrentStoreId)
                    ->setIsSystem(0)
                    ->setTargetPath($sourceCollectionUrl)
                    ->setRequestPath($requestCollectionUrl)
                    ->save();
            }

            /*
            * Set Seller Feedback Url
            */
            $sourceFeedbackUrl = 'marketplace/seller/feedback/shop/' . $profileurl;
            $requestFeedbackUrl = $profileurl . '/feedback';
            /*
            * Check if already rexist in url rewrite model
            */
            $urlId = '';
            $feedbackRequestUrl = '';
            $urlFeedbackData = $this->urlRewriteFactory->create()
                ->getCollection()
                ->addFieldToFilter('target_path', $sourceFeedbackUrl)
                ->addFieldToFilter('store_id', $getCurrentStoreId);
            foreach ($urlFeedbackData as $value) {
                $urlId = $value->getId();
                $feedbackRequestUrl = $value->getRequestPath();
            }
            if ($feedbackRequestUrl != $requestFeedbackUrl) {
                $this->urlRewriteFactory->create()->load($urlId)
                    ->setStoreId($getCurrentStoreId)
                    ->setIsSystem(0)
                    ->setTargetPath($sourceFeedbackUrl)
                    ->setRequestPath($requestFeedbackUrl)
                    ->save();
            }

            /*
            * Set Seller Location Url
            */
            $sourceLocationUrl = 'marketplace/seller/location/shop/' . $profileurl;
            $requestLocationUrl = $profileurl . '/location';
            /*
            * Check if already rexist in url rewrite model
            */
            $urlId = '';
            $locationRequestUrl = '';
            $urlLocationData = $this->urlRewriteFactory->create()
                ->getCollection()
                ->addFieldToFilter('target_path', $sourceLocationUrl)
                ->addFieldToFilter('store_id', $getCurrentStoreId);
            foreach ($urlLocationData as $value) {
                $urlId = $value->getId();
                $locationRequestUrl = $value->getRequestPath();
            }
            if ($locationRequestUrl != $requestLocationUrl) {
                $this->urlRewriteFactory->create()->load($urlId)
                    ->setStoreId($getCurrentStoreId)
                    ->setIsSystem(0)
                    ->setTargetPath($sourceLocationUrl)
                    ->setRequestPath($requestLocationUrl)
                    ->save();
            }

            /**
             * Set Seller Policy Url
             */
            $sourcePolicyUrl = 'marketplace/seller/policy/shop/' . $profileurl;
            $requestPolicyUrl = $profileurl . '/policy';
            /*
            * Check if already rexist in url rewrite model
            */
            $urlId = '';
            $policyRequestUrl = '';
            $urlPolicyData = $this->urlRewriteFactory->create()
                ->getCollection()
                ->addFieldToFilter('target_path', $sourcePolicyUrl)
                ->addFieldToFilter('store_id', $getCurrentStoreId);
            foreach ($urlPolicyData as $value) {
                $urlId = $value->getId();
                $policyRequestUrl = $value->getRequestPath();
            }
            if ($policyRequestUrl != $requestPolicyUrl) {
                $this->urlRewriteFactory->create()
                    ->load($urlId)
                    ->setStoreId($getCurrentStoreId)
                    ->setIsSystem(0)
                    ->setTargetPath($sourcePolicyUrl)
                    ->setRequestPath($requestPolicyUrl)
                    ->save();
            }
        }
    }
}