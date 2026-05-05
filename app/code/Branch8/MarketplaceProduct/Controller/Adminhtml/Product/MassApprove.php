<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Controller\Adminhtml\Product;

use Branch8\MarketplaceProduct\Helper\ProductApproval;
use Branch8\MarketplaceProduct\Model\MarketplaceProductManagement;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect as ResultRedirect;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Indexer\Product\Eav\Processor as EavProcessor;
use Magento\Catalog\Model\Indexer\Product\Price\Processor;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Catalog\Model\ProductFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\MassAction\Filter;
use Magento\User\Model\UserFactory;
use Psr\Log\LoggerInterface;
use Webkul\Marketplace\Helper\Data as DataHelper;
use Webkul\Marketplace\Helper\Email as EmailHelper;
use Webkul\Marketplace\Helper\Notification as MarketplaceNotificationHelper;
use Webkul\Marketplace\Model\Product;
use Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\MessageQueue\PublisherInterface;
use Branch8\MarketplaceProduct\Api\Data\ApproveProductDataInterfaceFactory;
use Magento\Framework\Authorization\PolicyInterface;

/**
 * Class MassApprove used to mass approved.
 */
class MassApprove extends Action
{
    /**
     * @var string
     */
    private const LOG_PREFIX = 'Branch8_MarketplaceProduct::MassApprove';

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var Filter
     */
    private Filter $filter;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * @var MarketplaceProductManagement
     */
    private MarketplaceProductManagement $marketplaceProductManagement;
    /**
     * @var \Branch8\MarketplaceSubAccount\Helper\Data
     */
    protected $b8SubAccountHelper;

    /**
     * @var DateTime
     */
    protected DateTime $dateTime;

    /**
     * @var PublisherInterface
     */
    private PublisherInterface $publisher;

    /**
     * @var ApproveProductDataInterfaceFactory
     */
    private ApproveProductDataInterfaceFactory $approveProductDataFactory;
    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $authSession;
    /**
     * @var PolicyInterface
     */
    protected $policy;

    protected $adminInfor = [];


    /**
     * @param Context $context
     * @param LoggerInterface $logger
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param MarketplaceProductManagement $marketplaceProductManagement
     * @param \Branch8\MarketplaceSubAccount\Helper\Data $b8SubAccountHelper
     * @param DateTime $dateTime
     * @param PublisherInterface $publisher
     * @param ApproveProductDataInterfaceFactory $approveProductDataFactory
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param PolicyInterface $policy
     */
    public function __construct(
        Context                       $context,
        LoggerInterface               $logger,
        Filter                        $filter,
        CollectionFactory             $collectionFactory,
        MarketplaceProductManagement  $marketplaceProductManagement,
        \Branch8\MarketplaceSubAccount\Helper\Data $b8SubAccountHelper,
        DateTime $dateTime,
        PublisherInterface $publisher,
        ApproveProductDataInterfaceFactory $approveProductDataFactory,
        \Magento\Backend\Model\Auth\Session $authSession,
        PolicyInterface $policy
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->marketplaceProductManagement = $marketplaceProductManagement;
        $this->b8SubAccountHelper = $b8SubAccountHelper;
        $this->dateTime = $dateTime;
        $this->publisher = $publisher;
        $this->approveProductDataFactory = $approveProductDataFactory;
        $this->authSession = $authSession;
        $this->policy = $policy;
    }

    /**
     * @inheritDoc
     *
     * @throws LocalizedException
     */
    public function execute()
    {
        //$productIds = [];
        $successful = $failed = 0;

        $collection = $this->filter->getCollection($this->collectionFactory->create());
        foreach ($collection as $item) {
            try {
                $approveProductData = $this->approveProductDataFactory->create();
                $approveProductData->setProductId((int)$item->getMageproductId());
                $approveProductData->setSellerId((int)$item->getSellerId());
                $approveProductData->setMarketplaceProductId((int)$item->getId());
                $approveProductData->setDateTime($this->dateTime->gmtTimestamp());

                $gridNamespace = $this->getRequest()->getParam('namespace');
                /** Get Reviewer info */
                if(empty($this->adminInfor)){
                    $this->adminInfor = $this->getReviewerInfo($gridNamespace);
                }
                $reviewInfor = json_encode($this->adminInfor);
                $approveProductData->setReviewerInfo($reviewInfor);
                
                $approveProductData->setGridNamespace($gridNamespace);

                $this->publisher->publish('branch8.marketplaceproduct.approve', $approveProductData);
                //$productIds[] = $productId;
                $successful++;
            } catch (\Exception $e) {
                $item->setData('status', Product::STATUS_PENDING);
                $item->setData('seller_pending_notification', 0);
                $item->setData('is_approved', 0);
                // Save error message to item for tracking
                $item->setData('approve_error_message', $e->getMessage());
                $this->marketplaceProductManagement->save($item);
                $this->logger->error(self::LOG_PREFIX, ['exception' => $e]);
                $failed++;
            }
        }

        $this->messageManager->addSuccessMessage(__('A total of %1 record(s) have been added to the queue, and will be processed within the next few minutes.', $successful));
        if ($failed) {
            $this->messageManager->addErrorMessage(__('There was an error when approving %1 record(s).', $failed));
        }

        /** @var ResultRedirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        if($gridNamespace == 'marketplacectrl_products_list'){
            return $resultRedirect->setPath('marketplacectrl/product');
        }else if($gridNamespace == 'marketplacectrl_manager_products_list'){
            return $resultRedirect->setPath('marketplacectrl/managerProduct');
        }else{
            return $resultRedirect->setPath('admin/dashboard/index');
        }
        
    }

    protected function getReviewerInfo($gridNamespace){
        $adminUser = $this->authSession->getUser();
        $username = $adminUser->getUserName();
        $adminRole = $adminUser->getRole()->getRoleName();
        
        $userStage = '';
        if($gridNamespace == 'marketplacectrl_products_list'){
            $userStage = 'Curator';
        }else if($gridNamespace == 'marketplacectrl_manager_products_list'){
            $userStage = 'Manager';
        }
        return [
            'id' => $adminUser->getId(),
            'name' => $username,
            'role_name' => $adminRole,
            'user_stage' => $userStage
        ];
    }

    /**
     * @inheritDoc
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Branch8_MarketplaceProduct::approve') || $this->_authorization->isAllowed('Branch8_MarketplaceProduct::manager_product_approval');
    }
}
