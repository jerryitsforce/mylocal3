<?php

namespace Branch8\MarketplaceApi\Override\Seller;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Email\Sender\CreditmemoSender;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\ShipmentSender;
use Magento\Sales\Model\Order\ShipmentFactory;
use Webkul\MpApi\Api\FeedbackRepositoryInterface;

class SellerManagement extends \Webkul\MpApi\Model\Seller\SellerManagement
{
    private \Magento\Sales\Api\ShipmentRepositoryInterface $_shipmentRepository;
    private \Webkul\MpApi\Api\SaleslistRepositoryInterface $salesListRepo;
    private \Webkul\MpApi\Api\SellerRepositoryInterface $sellerRepo;
    private \Webkul\MpApi\Api\OrdersRepositoryInterface $ordersRepo;
    private FeedbackRepositoryInterface $feedbackRepo;
    private \Magento\Framework\Api\Search\FilterGroupFactory $filterGroup;
    private \Magento\Catalog\Model\ProductFactory $_product;
    private \Magento\Framework\Api\FilterFactory $filter;
    private \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria;
    private \Magento\Catalog\Model\ProductRepository\MediaGalleryProcessor $mediaProcessor;
    private AccountManagementInterface $accountManagement;

    protected $customerCollectionFactory;

    public function __construct(
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Webkul\MpApi\Api\SaleslistRepositoryInterface $salesListRepo,
        \Webkul\MpApi\Api\SellerRepositoryInterface $sellerRepo,
        \Webkul\MpApi\Api\OrdersRepositoryInterface $ordersRepo,
        FeedbackRepositoryInterface $feedbackRepo,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Customer\Api\Data\CustomerInterface $customerInterface,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Directory\Model\CountryFactory $country,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Psr\Log\LoggerInterface $logger,
        \Webkul\Marketplace\Api\Data\SellerInterfaceFactory $sellerFactory,
        \Webkul\Marketplace\Helper\Data $marketplaceHelper,
        OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository,
        CreditmemoFactory $creditmemoFactory,
        InvoiceSender $invoiceSender,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        ShipmentFactory $shipmentFactory,
        ShipmentSender $shipmentSender,
        CreditmemoSender $creditmemoSender,
        \Magento\Framework\Event\Manager $eventManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepoInterface,
        \Webkul\Marketplace\Api\Data\ProductInterfaceFactory $mpProductFactory,
        \Webkul\Marketplace\Api\Data\SaleslistInterfaceFactory $saleslistFactory,
        \Magento\Framework\Filesystem\DirectoryList $directoryList,
        \Webkul\Marketplace\Api\Data\OrdersInterfaceFactory $mpOrdersFactory,
        \Magento\Sales\Api\InvoiceManagementInterface $invoiceManagementInterface,
        \Magento\Framework\DB\Transaction $dbTransaction,
        \Webkul\Marketplace\Helper\Email $emailHelper,
        \Magento\Backend\Model\Url $backendUrl,
        \Magento\UrlRewrite\Model\UrlRewriteFactory $urlRewriteFactory,
        \Magento\Framework\UrlInterface $urlInterface,
        \Webkul\Marketplace\Helper\Orders $orderHelper,
        \Magento\Sales\Api\CreditmemoManagementInterface $creditmemoManagementInterface,
        \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepositoryInterface,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Webkul\MpApi\Api\Data\FeedbackInterfaceFactory $feedbackFactory,
        \Webkul\Marketplace\Api\Data\FeedbackcountInterfaceFactory $feedbackcountFactory,
        \Webkul\MpApi\Api\Data\ResponseInterface $responseInterface,
        \Magento\Framework\Filesystem\Driver\File $driverFile,
        \Magento\Framework\Filesystem\Io\File $file,
        \Webkul\Marketplace\Controller\Product\SaveProduct $saveProduct,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Magento\Shipping\Model\CarrierFactory $carrierFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria,
        \Magento\Framework\Api\FilterFactory $filter,
        \Magento\Framework\Api\Search\FilterGroupFactory $filterGroup,
        \Magento\Catalog\Model\ProductRepository\MediaGalleryProcessor $mediaProcessor,
        AccountManagementInterface $accountManagement,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory
    ) {
        parent::__construct(
            $customerFactory, $salesListRepo, $sellerRepo, $ordersRepo, $feedbackRepo,
            $customerSession, $customerInterface, $customerRepository, $country, $storeManager, $logger,
            $sellerFactory, $marketplaceHelper, $orderRepository, $invoiceRepository, $creditmemoFactory,
            $invoiceSender, $date, $resourceConnection, $shipmentFactory, $shipmentSender, $creditmemoSender,
            $eventManager, $productRepoInterface, $mpProductFactory, $saleslistFactory, $directoryList, $mpOrdersFactory,
            $invoiceManagementInterface, $dbTransaction, $emailHelper, $backendUrl, $urlRewriteFactory,
            $urlInterface, $orderHelper, $creditmemoManagementInterface, $creditmemoRepositoryInterface, $imageHelper,
            $feedbackFactory, $feedbackcountFactory, $responseInterface, $driverFile, $file, $saveProduct,
            $shipmentRepository,$carrierFactory, $jsonHelper, $searchCriteria, $filter, $filterGroup, $mediaProcessor,
            $accountManagement, $productFactory
        );
        $this->salesListRepo = $salesListRepo;
        $this->sellerRepo = $sellerRepo;
        $this->ordersRepo = $ordersRepo;
        $this->feedbackRepo = $feedbackRepo;
        $this->_shipmentRepository = $shipmentRepository;
        $this->filterGroup = $filterGroup;
        $this->filter = $filter;
        $this->searchCriteria = $searchCriteria;
        $this->mediaProcessor = $mediaProcessor;
        $this->_product = $productFactory;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->accountManagement = $accountManagement;
    }

    public function createSellerAccount(
        \Magento\Customer\Api\Data\CustomerInterface $customer,
                                                     $password,
                                                    $profileurl
    ){
        $returnArray = [];

        //validate shop url
        $model = $this->sellerFactory->create()->getCollection()->addFieldToFilter(
            'shop_url',
            $profileurl
        );

        if ($model->getSize()) {
            $returnArray['status'] = self::SEVERE_ERROR;
            $returnArray['message'] = __(
                'Sorry! But this shop name is not available, please set another shop name.'
            );
            return $this->getJsonResponse($returnArray);
        }
        //validate existed
        $customerCollection = $this->customerCollectionFactory->create()
            ->addAttributeToFilter('email', $customer->getEmail());
        if($customerCollection->getSize()){
            $returnArray['status'] = self::SEVERE_ERROR;
            $returnArray['message'] = __(
                'Sorry! A seller account with this email already exists.'
            );
            return $this->getJsonResponse($returnArray);
        }

        try {
            $customer->setCustomAttribute('platform', 'seller');
            $customer = $this->accountManagement->createAccount($customer, $password, "");
            $sellerCreated = $this->createSeller($customer, $profileurl);
            return $this->responseForCreateAccount(
                $sellerCreated,
                $customer,
                $returnArray,
                $profileurl
            );
        } catch (\Exception $e) {
            $returnArray['status'] = self::SEVERE_ERROR;
            $returnArray['message'] = $e->getMessage();
            return $this->getJsonResponse($returnArray);
        }
    }



    public function createAccount(
        \Magento\Customer\Api\Data\CustomerInterface $customer,
                                                     $password,
                                                     $isSeller,
                                                     $profileurl,
                                                     $registered
    ) {
        $customerData = [];
        $returnArray = [];
        $returnArray['status'] = self::SEVERE_ERROR;
        $returnArray['message'] = __('Please API /rest/V1/mpapi/sellers/createaccount');
        return $this->getJsonResponse($returnArray);
    }

    /**
     * Merge two arrays.
     *
     * @param array $item
     * @param array $data
     *
     * @return array
     */
    private function arrayMerge($item, $data)
    {
        return array_merge($item, $data);
    }
    /**
     * Create seller public URLs.
     *
     * @param string $profileurl
     */
    private function createSellerPublicUrls($profileurl = '')
    {
        if ($profileurl) {
            $getCurrentStoreId = $this->mpHelper->getCurrentStoreId();

            /**
             * Set Seller Profile Url
             */
            $sourceProfileUrl = 'marketplace/seller/profile/shop/'.$profileurl;
            $requestProfileUrl = $profileurl;
            /**
             * Check if already exist in url rewrite model
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

            /**
             * Set Seller Collection Url
             */
            $sourceCollectionUrl = 'marketplace/seller/collection/shop/'.$profileurl;
            $requestCollectionUrl = $profileurl.'/collection';
            /**
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
                $this->urlRewriteFactory->create()
                    ->load($urlId)
                    ->setStoreId($getCurrentStoreId)
                    ->setIsSystem(0)
                    ->setTargetPath($sourceCollectionUrl)
                    ->setRequestPath($requestCollectionUrl)
                    ->save();
            }

            /**
             * Set Seller Feedback Url
             */
            $sourceFeedbackUrl = 'marketplace/seller/feedback/shop/'.$profileurl;
            $requestFeedbackUrl = $profileurl.'/feedback';
            /**
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
                $this->urlRewriteFactory->create()
                    ->load($urlId)
                    ->setStoreId($getCurrentStoreId)
                    ->setIsSystem(0)
                    ->setTargetPath($sourceFeedbackUrl)
                    ->setRequestPath($requestFeedbackUrl)
                    ->save();
            }

            /**
             * Set Seller Location Url
             */
            $sourceLocationUrl = 'marketplace/seller/location/shop/'.$profileurl;
            $requestLocationUrl = $profileurl.'/location';
            /**
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
                $this->urlRewriteFactory->create()
                    ->load($urlId)
                    ->setStoreId($getCurrentStoreId)
                    ->setIsSystem(0)
                    ->setTargetPath($sourceLocationUrl)
                    ->setRequestPath($requestLocationUrl)
                    ->save();
            }

            /**
             * Set Seller Policy Url
             */
            $sourcePolicyUrl = 'marketplace/seller/policy/shop/'.$profileurl;
            $requestPolicyUrl = $profileurl.'/policy';
            /**
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
    private function createSeller($customer, $profileurl)
    {
        $profileurlcount = $this->sellerFactory->create()->getCollection();
        $profileurlcount->addFieldToFilter(
            ['shop_url','seller_id'],
            [$profileurl,$customer->getId()]
        );
        if ($profileurlcount->getSize() == 0) {
            $status = $this->mpHelper->getIsPartnerApproval() ? 0 : 1;
            $customerid = $customer->getId();
            $model = $this->sellerFactory->create();
            $model->setData('is_seller', $status);
            $model->setData('shop_url', $profileurl);
            $model->setData('seller_id', $customerid);
            $model->setData('store_id', 0);
            $model->setCreatedAt($this->date->gmtDate());
            $model->setUpdatedAt($this->date->gmtDate());
            if ($status == 0) {
                $model->setAdminNotification(1);
            }
            $model->save();
            $loginUrl = $this->urlInterface->getUrl("marketplace/account/dashboard");
            $this->customerSession->setBeforeAuthUrl($loginUrl);
            $this->customerSession->setAfterAuthUrl($loginUrl);

            $helper = $this->mpHelper;
            if ($helper->getAutomaticUrlRewrite()) {
                $this->createSellerPublicUrls($profileurl);
            }
            $adminStoremail = $helper->getAdminEmailId();
            $adminEmail = $adminStoremail ? $adminStoremail : $helper->getDefaultTransEmailId();
            $adminUsername = 'Admin';
            $senderInfo = [
                'name' => $customer->getFirstName().' '.$customer->getLastName(),
                'email' => $customer->getEmail(),
            ];
            $receiverInfo = [
                'name' => $adminUsername,
                'email' => $adminEmail,
            ];
            $emailTemplateVariables['myvar1'] = $customer->getFirstName().' '.
                $customer->getLastName();
            $emailTemplateVariables['myvar2'] = $this->backendUrl->getUrl(
                'customer/index/edit',
                ['id' => $customer->getId()]
            );
            $emailTemplateVariables['myvar3'] = 'Admin';

            $this->emailHelper->sendNewSellerRequest(
                $emailTemplateVariables,
                $senderInfo,
                $receiverInfo
            );
            return true;
        } else {
            return false;
        }
    }
    private function getOrder($orderId)
    {
        $order = $this->mageOrderRepo->get($orderId);
        if ($order->getId()) {
            return $order;
        } else {
            throw \NoSuchEntityException::singleField('orderId', $orderId);
        }
    }
}
