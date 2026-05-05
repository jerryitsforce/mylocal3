<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Branch8\MarketplaceProduct\Observer;

use Branch8\MarketplaceProduct\Api\ProductVersionRepositoryInterface;
use Branch8\MarketplaceProduct\Model\ResourceModel\GetProductLogEntryByProductId;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Webkul\Marketplace\Model\Product;
use Webkul\Marketplace\Model\ProductFactory as MpProductFactory;
use Webkul\Marketplace\Helper\Data as MpHelper;
use Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory;

/**
 * Webkul Marketplace CatalogProductSaveAfterObserver Observer.
 */
class ProductScheduleSaveAfterObserver implements ObserverInterface
{
    /**
     * @var MpProductFactory
     */
    protected $mpProductFactory;

    /**
     * @var CollectionFactory
     */
    protected $_collectionFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var MpHelper
     */
    protected $mpHelper;

    /**
     * @var \Magento\Framework\App\State
     */
    protected \Magento\Framework\App\State $_state;

    /**
     * @var ProductVersionRepositoryInterface
     */
    private ProductVersionRepositoryInterface $productVersionRepository;

    /**
     * @var GetProductLogEntryByProductId
     */
    private GetProductLogEntryByProductId $getProductLogEntryByProductId;

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var AdminSession
     */
    private AdminSession $adminSession;

    /**
     * Array of cached product run.
     *
     * @var array
     */
    protected $_cachedProductRowIds = [];

    /**
     * @param DateTime $date
     * @param CollectionFactory $collectionFactory
     * @param ManagerInterface $messageManager
     * @param \Magento\Framework\App\State $state
     * @param ProductVersionRepositoryInterface $productVersionRepository
     * @param GetProductLogEntryByProductId $getProductLogEntryByProductId
     * @param RequestInterface $request
     * @param AdminSession|null $adminSession
     * @param MpProductFactory|null $mpProductFactory
     * @param MpHelper|null $mpHelper
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        CollectionFactory $collectionFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\State $state,
        ProductVersionRepositoryInterface $productVersionRepository,
        GetProductLogEntryByProductId $getProductLogEntryByProductId,
        RequestInterface $request,
        AdminSession $adminSession = null,
        MpProductFactory $mpProductFactory = null,
        MpHelper $mpHelper = null
    ) {
        $this->_collectionFactory = $collectionFactory;
        $this->_date = $date;
        $this->messageManager = $messageManager;
        $this->_state = $state;
        $this->productVersionRepository = $productVersionRepository;
        $this->getProductLogEntryByProductId = $getProductLogEntryByProductId;
        $this->request = $request;
        $this->adminSession = $adminSession ?? ObjectManager::getInstance()->get(AdminSession::class);
        $this->mpProductFactory = $mpProductFactory ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(MpProductFactory::class);
        $this->mpHelper = $mpHelper ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(MpHelper::class);
    }

    /**
     * Product save after event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $entity = $observer->getEntity();
        $entityType = $observer->getEntityType();
        if ($entityType == 'Magento\Catalog\Api\Data\ProductInterface') {
            try {
                $product = $entity;
                $assginSellerData = $product->getAssignSeller();
                $productId = $product->getId();
                $productRowId = $product->getRowId();
                if (!isset($this->_cachedProductRowIds[$productRowId])) {
                    $this->_cachedProductRowIds[$productRowId] = $productRowId;
                } else {
                    return;
                }
                $status = $product->getStatus();
                $extensionAttribute = $product->getExtensionAttributes();
                $apiMpProductStatus = $extensionAttribute->getMpProductStatus();
                if($apiMpProductStatus !== null){
                    $status = $apiMpProductStatus;
                }
                $sellerProductColl = $this->mpProductFactory->create()->getCollection()
                    ->addFieldToFilter('mage_pro_row_id', $productRowId)
                    ->addFieldToFilter('mageproduct_id', $productId)
                    ->setPageSize(1)
                    ->setCurPage(1)
                    ->getFirstItem();
                //$attributeSelected = $this->request->getParam('attribute_selected');
                $controller = $this->request->getRouteName();
                $action = $this->request->getActionName();
                if (in_array($controller, ['GeneralNotifyTicket', 'GeneralNonNotifyTicket', 'family_bonus_pin', 'yoxi']) && $action == 'ReceiveGridForm') {
                    return; // Skip this observer for Family Bonus Pin ReceiveGridForm action
                }
                if ($controller == 'marketplacectrl') {
                    if ($action == 'approve' || $action == 'massApprove') {
                        $status = Product::STATUS_ENABLED;
                    } elseif ($action == 'disapprove' || $action == 'massDisapprove') {
                        $status = Product::STATUS_DISABLED;
                    }
                }
                if ($sellerProductColl->getId()) {
                    if ($status != $sellerProductColl->getStatus()) {
                        /*if ($attributeSelected && is_array($attributeSelected)) {
                            $attributeSelected = implode(',', $attributeSelected);
                            $sellerProductColl->setStatus($status)->setAttributeSelected($attributeSelected)->save();
                        } else {*/
                            $sellerProductColl->setStatus($status)->save();
                        //}
                    }
                    // Disable all versions of the product if the product is disabled by admin
                    if ($this->_state->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML) {
                        if ($status != Product::STATUS_PENDING) {
                            $logEntries = $this->getProductLogEntryByProductId->executeAll($productId);
                            foreach ($logEntries as $logEntry) {
                                if (!$logEntry['id']) continue;
                                $productVersion = $this->productVersionRepository->getById((int)$logEntry['id']);
                                $productVersion->setStatus(Product::STATUS_DISABLED);

                                if ($this->adminSession->isLoggedIn()) {
                                    $adminUser = $this->adminSession->getUser();
                                    $productVersion->setReviewerId((int)$adminUser->getId());
                                }
                                $this->productVersionRepository->save($productVersion);
                            }
                        }
                    }
                } else {
                    $sellerProductFactory = $this->mpProductFactory->create()
                        ->getCollection()
                        ->addFieldToFilter('mageproduct_id', $productId)
                        ->setPageSize(1)
                        ->setCurPage(1)
                        ->getFirstItem();
                    if ($sellerProductFactory->getId()) {
                        $sellerId = $sellerProductFactory->getSellerId();
                        $sellerProduct = $this->mpProductFactory->create();
                        $sellerProduct->setMageproductId($productId);
                        $sellerProduct->setMageProRowId($productRowId);
                        $sellerProduct->setSellerId($sellerId);
                        $sellerProduct->setStatus($status);
                        $sellerProduct->setUpdatedAt($this->_date->gmtDate());
                        /*if ($attributeSelected && is_array($attributeSelected)) {
                            $attributeSelected = implode(',', $attributeSelected);
                            $sellerProduct->setAttributeSelected($attributeSelected);
                        }*/
                        $sellerProduct->save();
                        $sellerProductFactory->setStatus(0)->setIsApproved(0)->setDealerApproveStatus(0)->save();
                    } elseif (is_array($assginSellerData) &&
                        isset($assginSellerData['seller_id']) &&
                        $assginSellerData['seller_id'] != ''
                    ) {
                        $sellerId = $assginSellerData['seller_id'];
                        $mpProductModel = $this->mpProductFactory->create();
                        $mpProductModel->setMageProRowId($productRowId);
                        $mpProductModel->setMageproductId($productId);
                        $mpProductModel->setSellerId($sellerId);
                        $mpProductModel->setStatus($product->getStatus());
                        $mpProductModel->setAdminassign(1);
                        $isApproved = 1;
                        if ($product->getStatus() == 2 && $this->mpHelper->getIsProductApproval()) {
                            $isApproved = 0;
                        }
                        $mpProductModel->setIsApproved($isApproved);
                        $mpProductModel->setCreatedAt($this->_date->gmtDate());
                        $mpProductModel->setUpdatedAt($this->_date->gmtDate());
                        /*if ($attributeSelected && is_array($attributeSelected)) {
                            $attributeSelected = implode(',', $attributeSelected);
                            $mpProductModel->setAttributeSelected($attributeSelected);
                        }*/
                        $mpProductModel->save();
                    }
                }
            } catch (\Exception $e) {
                $this->mpHelper->logDataInLogger(
                    "Observer_CatalogProductSaveAfterObserver execute : ".$e->getMessage()
                );
                $this->messageManager->addError($e->getMessage());
            }
        }
    }
}
