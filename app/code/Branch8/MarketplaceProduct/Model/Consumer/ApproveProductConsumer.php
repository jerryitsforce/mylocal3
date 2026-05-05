<?php
// filepath: app/code/Branch8/MarketplaceProduct/Model/Consumer/ApproveProductConsumer.php

namespace Branch8\MarketplaceProduct\Model\Consumer;

use Branch8\MarketplaceProduct\Helper\ProductApproval;
use Branch8\MarketplaceProduct\Model\MarketplaceProductManagement;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\State;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface;
use Webkul\Marketplace\Helper\Data as MpHelper;
use Webkul\Marketplace\Helper\Email as MpEmailHelper;
use Webkul\Marketplace\Helper\Notification as NotificationHelper;
use Webkul\Marketplace\Model\Notification;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\User\Model\UserFactory;
use Branch8\MarketplaceSubAccount\Helper\Data as B8SubAccountHelper;
use Branch8\MarketplaceProduct\Api\Data\ApproveProductDataInterface;
use Branch8\MarketplaceProduct\Model\ResourceModel\GetProductLogEntryByProductId;
use Branch8\MarketplaceProduct\Model\Config\Source\ApprovalFlowStatus;

class ApproveProductConsumer
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MarketplaceProductManagement
     */
    private $marketplaceProductManagement;

    /**
     * @var ProductApproval
     */
    private $productApproval;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var NotificationHelper
     */
    private $mpNotificationHelper;

    /**
     * @var MpHelper
     */
    private $mpHelper;

    /**
     * @var MpEmailHelper
     */
    private $mpEmailHelper;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * @var B8SubAccountHelper
     */
    private $b8SubAccountHelper;

    private State $state;

    protected $getProductLogEntryByProductId;

    /**
     * @param LoggerInterface $logger
     * @param MarketplaceProductManagement $marketplaceProductManagement
     * @param ProductApproval $productApproval
     * @param ProductRepositoryInterface $productRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param EventManager $eventManager
     * @param NotificationHelper $mpNotificationHelper
     * @param MpHelper $mpHelper
     * @param MpEmailHelper $mpEmailHelper
     * @param CategoryRepositoryInterface $categoryRepository
     * @param UserFactory $userFactory
     * @param State $state
     * @param B8SubAccountHelper $b8SubAccountHelper
     * @param GetProductLogEntryByProductId $getProductLogEntryByProductId
     */
    public function __construct(
        LoggerInterface $logger,
        MarketplaceProductManagement $marketplaceProductManagement,
        ProductApproval $productApproval,
        ProductRepositoryInterface $productRepository,
        CustomerRepositoryInterface $customerRepository,
        EventManager $eventManager,
        NotificationHelper $mpNotificationHelper,
        MpHelper $mpHelper,
        MpEmailHelper $mpEmailHelper,
        CategoryRepositoryInterface $categoryRepository,
        UserFactory $userFactory,
        State $state,
        B8SubAccountHelper $b8SubAccountHelper,
        GetProductLogEntryByProductId $getProductLogEntryByProductId
    ) {
        $this->logger = $logger;
        $this->marketplaceProductManagement = $marketplaceProductManagement;
        $this->productApproval = $productApproval;
        $this->productRepository = $productRepository;
        $this->customerRepository = $customerRepository;
        $this->eventManager = $eventManager;
        $this->mpNotificationHelper = $mpNotificationHelper;
        $this->mpHelper = $mpHelper;
        $this->mpEmailHelper = $mpEmailHelper;
        $this->categoryRepository = $categoryRepository;
        $this->userFactory = $userFactory;
        $this->b8SubAccountHelper = $b8SubAccountHelper;
        $this->state=$state;
        $this->getProductLogEntryByProductId = $getProductLogEntryByProductId;
    }

    /**
     * Consumer for branch8.marketplaceproduct.approve topic
     * @param ApproveProductDataInterface $message
     */
    public function process(ApproveProductDataInterface $message)
    {
        try {
            $this->logger->info("ApproveProductDataInterface:".print_r($message, true));
            $productId = (int)($message->getProductId() ?? 0);
            $sellerId = (int)($message->getSellerId() ?? 0);
            $marketplaceProductId = (int)($message->getMarketplaceProductId() ?? 0);

            if (!$productId || !$sellerId || !$marketplaceProductId) {
                $this->logger->error('[ApproveProductConsumer] Missing required data');
                return;
            }

            
            $reviewerInfor = (string)$message->getReviewerInfo();
            try{
                $reviewerInforData = json_decode($reviewerInfor, true);
                if(isset($reviewerInforData['id'])){
                    $reviewerId = $reviewerInforData['id'];
                }else{
                    $reviewerId = '';
                }
            }catch(\Exception $e){
                $this->logger->debug('[ApproveProductConsumer] Missing reviewerInfo data');
                $reviewerId = '';
            }

            $logEntry = $this->getProductLogEntryByProductId->execute($productId);
            if(!isset($logEntry['id'])){
                return;
            }
            $approvalFlowStatus = (int)$logEntry['approval_flow_status'];
            $allowFlowStatus = [ApprovalFlowStatus::DISTRIBUTOR_PENDING_APPROVAL, ApprovalFlowStatus::CURATOR_PENDING_APPROVAL, ApprovalFlowStatus::MANAGER_PENDING_APPROVAL];
            $gridNamespace = $message->getGridNamespace();
            if(
                ($approvalFlowStatus > 0 && !in_array($approvalFlowStatus, $allowFlowStatus))
                || ($gridNamespace == 'marketplacectrl_products_list' && $approvalFlowStatus > 0 && !in_array($approvalFlowStatus, [ApprovalFlowStatus::DISTRIBUTOR_PENDING_APPROVAL, ApprovalFlowStatus::CURATOR_PENDING_APPROVAL]))
                || ($gridNamespace == 'marketplacectrl_manager_products_list' && $approvalFlowStatus > 0 && $approvalFlowStatus != ApprovalFlowStatus::MANAGER_PENDING_APPROVAL)
            ){
                return;
            }
            /** if commission_percent < 0, only change status, if not, approve and apply data */
            if((float)$logEntry['commission_percent'] < 0 || $this->productApproval->isVariantionNegative($logEntry)){
                if(in_array($logEntry['approval_flow_status'], [ApprovalFlowStatus::DISTRIBUTOR_PENDING_APPROVAL, ApprovalFlowStatus::CURATOR_PENDING_APPROVAL])){
                    /**
                     * For Curator pending, only change status to Manager pending
                     */
                    $reviewerInfor = json_decode((string)$message->getReviewerInfo(), true);
                    
                    $this->productApproval->massApproveProcessNegativeGrossProfit($logEntry['id'], $reviewerInfor);
                    return;
                }
            }

            list($userUpdated, $isNewProduct) = $this->productApproval->handleApprovalBasedOnLogEntry($productId, $sellerId, $reviewerId, $gridNamespace);

            $item = $this->marketplaceProductManagement->getById($marketplaceProductId);
            $item->setData('status', 1); // STATUS_ENABLED
            $item->setData('seller_pending_notification', 1);
            $item->setData('new_need_approve', 0);
            $item->setData('is_approved', 1);
            if ($isNewProduct) {
                $item->setData('first_enabled_date', (new \DateTime())->format('Y-m-d H:i:s'));
            }
            $item = $this->marketplaceProductManagement->save($item);

            $this->mpNotificationHelper->saveNotification(Notification::TYPE_PRODUCT, $item->getId(), $productId);

            $productModel = $this->productRepository->getById($productId);

            $categoryIds = $productModel->getCategoryIds();
            $categoryName = false;
            foreach ($categoryIds as $categoryId) {
                $category = $this->categoryRepository->get($categoryId);
                if (!$categoryName) {
                    $categoryName = $category->getName();
                } else {
                    $categoryName = $categoryName . ',' . $category->getName();
                }
            }

            $adminStoreEmail = $this->mpHelper->getAdminEmailId();
            $adminEmail = $adminStoreEmail ?: $this->mpHelper->getDefaultTransEmailId();
            $adminName = $this->mpHelper->getAdminName();
            // $dealerInfo = [];
            // if ($reviewerId) {
            //     $dealer = $this->userFactory->create()->load($reviewerId);
            //     $dealerName = $dealer->getName();
            //     $dealerEmail = $dealer->getEmail();
            //     $dealerInfo = ['name' => $dealerName, 'email' => $dealerEmail];
            // }

            $seller = $this->customerRepository->getById($sellerId);
            $sellerName = sprintf('%s %s', $seller->getFirstname(), $seller->getLastname());

            $emailTemplateVariables = [];
            $emailTemplateVariables['myvar1'] = $productModel->getName();
            $emailTemplateVariables['myvar2'] = $productModel->getDescription();
            $emailTemplateVariables['myvar3'] = $productModel->getPrice();
            $emailTemplateVariables['myvar4'] = $categoryName;
            $emailTemplateVariables['myvar5'] = $sellerName;
            $emailTemplateVariables['myvar6'] = 'I would like to inform you that your product has been approved.';
            $senderInfo = ['name' => $adminName, 'email' => $adminEmail];
            $receiverInfo = ['name' => $sellerName, 'email' => $seller->getEmail()];

            $this->mpEmailHelper->sendProductStatusMail($emailTemplateVariables, $senderInfo, $receiverInfo);
            // if ($reviewerId) {
            //     $this->mpEmailHelper->sendProductStatusMail($emailTemplateVariables, $senderInfo, $dealerInfo);
            // }
            /**
             * Send mail to Sub accounts
             */
            $this->b8SubAccountHelper->sendProductStatusMailToSubAccount($sellerId, $emailTemplateVariables, $senderInfo);

            // Dispatch event if needed
            $this->eventManager->dispatch('mp_approve_product', [
                'product' => $item,
                'seller_id' => $sellerId,
                'user_updated' => $userUpdated
            ]);

            $this->logger->info('[ApproveProductConsumer] Approved product', [
                'product_id' => $productId,
                'seller_id' => $sellerId,
                'marketplace_product_id' => $marketplaceProductId
            ]);
        } catch (\Exception $e) {
            $this->logger->error('[ApproveProductConsumer] Error: ' . $e->getMessage());
            $this->logger->debug($e->getTraceAsString());
        }
    }
}
