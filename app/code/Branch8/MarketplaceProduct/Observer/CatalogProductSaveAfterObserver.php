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
use Branch8\MarketplaceProduct\Model\Config as B8MpConfig;
use Branch8\MarketplaceProduct\Model\ResourceModel\GetProductLogEntryByProductId;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Webkul\Marketplace\Model\Product;
use Webkul\Marketplace\Model\ProductFactory as MpProductFactory;
use Webkul\Marketplace\Helper\Data as MpHelper;
use Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory as AttributeSetCollectionFactory;

/**
 * Webkul Marketplace CatalogProductSaveAfterObserver Observer.
 */
class CatalogProductSaveAfterObserver implements ObserverInterface
{
    /**
     * @var AttributeSetCollectionFactory
     */
    private AttributeSetCollectionFactory $attributeSetCollectionFactory;
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
     * @var B8MpConfig
     */
    protected B8MpConfig $b8MpConfig;

    /**
     * Array of cached product run.
     *
     * @var array
     */
    protected $_cachedProductRowIds = [];

    protected $stockRegistry;

    /**
     * @param DateTime $date
     * @param CollectionFactory $collectionFactory
     * @param ManagerInterface $messageManager
     * @param \Magento\Framework\App\State $state
     * @param ProductVersionRepositoryInterface $productVersionRepository
     * @param GetProductLogEntryByProductId $getProductLogEntryByProductId
     * @param RequestInterface $request
     * @param B8MpConfig $b8MpConfig
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
        B8MpConfig $b8MpConfig,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        AttributeSetCollectionFactory $attributeSetCollectionFactory,
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
        $this->b8MpConfig = $b8MpConfig;
        $this->adminSession = $adminSession ?? ObjectManager::getInstance()->get(AdminSession::class);
        $this->mpProductFactory = $mpProductFactory ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(MpProductFactory::class);
        $this->mpHelper = $mpHelper ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(MpHelper::class);
        $this->stockRegistry = $stockRegistry;
        $this->attributeSetCollectionFactory = $attributeSetCollectionFactory;
    }

    /**
     * Product save after event handler.
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $product = $observer->getProduct();
            $assginSellerData = $product->getAssignSeller();
            if ($product->getSellerId()) {
                $assginSellerData['seller_id'] = $product->getSellerId();
            }
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
            /* $attributeSelector = $this->b8MpConfig->getAttributeSelector();
             $flagSetBlank = false;
             if (empty($attributeSelected) && $this->_state->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML && $controller != 'marketplacectrl') {
                 foreach ($attributeSelector as $attribute) {
                     if ($product->getData($attribute)) {
                         $attributeSelected[] = $attribute;
                     }
                 }
                 if (empty($attributeSelected)) {
                     $flagSetBlank = true;
                 }
             }*/
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
                // Update seller_id if admin changed the assign_seller field
                if (is_array($assginSellerData)
                    && isset($assginSellerData['seller_id'])
                    && $assginSellerData['seller_id'] != ''
                    && (int)$assginSellerData['seller_id'] !== (int)$sellerProductColl->getSellerId()
                    && $this->_state->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML
                ) {
                    $sellerProductColl->setSellerId((int)$assginSellerData['seller_id']);
                    $sellerProductColl->setUpdatedAt($this->_date->gmtDate());
                    $sellerProductColl->save();
                }
                if ($status != $sellerProductColl->getStatus()) {
                    /*if ($attributeSelected && is_array($attributeSelected)) {
                        $attributeSelected = implode(',', $attributeSelected);
                        $sellerProductColl->setStatus($status)->setData('attribute_selected', $attributeSelected)->save();
                    } elseif ($flagSetBlank) {
                        $sellerProductColl->setStatus($status)->setData('attribute_selected', '')->save();
                    } else {*/
                        $sellerProductColl->setStatus($status)->save();
                    //}
                } /*else {
                    if ($attributeSelected && is_array($attributeSelected)) {
                        $attributeSelected = implode(',', $attributeSelected);
                        $sellerProductColl->setData('attribute_selected', $attributeSelected)->save();
                    } elseif ($flagSetBlank) {
                        $sellerProductColl->setData('attribute_selected', '')->save();
                    }
                }*/
                // Disable all versions of the product if the product is disabled by admin
                if ($this->_state->getAreaCode() == \Magento\Framework\App\Area::AREA_ADMINHTML) {
                    if ($status != Product::STATUS_PENDING) {
                        $logEntries = $this->getProductLogEntryByProductId->executeAll($productId);
                        foreach ($logEntries as $logEntry) {
                            if (!$logEntry['id']) continue;
                            $productVersion = $this->productVersionRepository->getById((int)$logEntry['id']);
                            $productVersion->setStatus($status);

                            if ($this->adminSession->isLoggedIn()) {
                                $adminUser = $this->adminSession->getUser();
                                $productVersion->setReviewerId((int)$adminUser->getId());
                            }
                            $this->productVersionRepository->save($productVersion);
                        }
                        $sellerProductPendingList = $this->mpProductFactory->create()->getCollection()
                            ->addFieldToFilter('mageproduct_id', $productId)
                            ->addFieldToFilter('entity_id', ['neq' => $sellerProductColl->getId()])
                            ->addFieldToFilter('status', Product::STATUS_PENDING);
                        if ($sellerProductPendingList->getSize()) {
                            foreach ($sellerProductPendingList as $sellerProductPending) {
                                $sellerProductPending->setStatus($status)->save();
                            }
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
//                    $sellerProduct->setStatus($status);
                    $sellerProduct->setUpdatedAt($this->_date->gmtDate());
                    /*if ($attributeSelected && is_array($attributeSelected)) {
                        $attributeSelected = implode(',', $attributeSelected);
                        $sellerProduct->setData('attribute_selected', $attributeSelected);
                    } elseif ($flagSetBlank) {
                        $sellerProduct->setData('attribute_selected', '');
                    }*/

                    if ($this->_state->getAreaCode() == \Magento\Framework\App\Area::AREA_CRONTAB) {
                        $sellerProduct->setStatus(Product::STATUS_ENABLED)->setIsApproved(1);
                        $sellerProduct->save();
                    } else {
                         $sellerProduct->save();
                        if ($controller == 'marketplacectrl' || $controller == 'marketplacestaging'|| $controller == 'catalogstaging') {
                            $sellerProductFactory->setIsApproved(1)->save();
                        } else {
                            $sellerProductFactory->setStatus(0)->setIsApproved(0)->setDealerApproveStatus(0)->save();
                        }
                    }
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
                        $mpProductModel->setData('attribute_selected', $attributeSelected);
                    } elseif ($flagSetBlank) {
                        $mpProductModel->setData('attribute_selected', '');
                    }*/
                    $mpProductModel->save();
                }
            }
            if(!$product->getOrigData('entity_id')){
                $ticketEdenred = $this->getAttributeSetIdByName('ticket_edenred');
                /*$sellerId = $this->mpHelper->getSellerIdByProductId($product->getId());
                $conn = $this->_collectionFactory->create()->getConnection();
                $sellerCodeQuery = $conn->select()
                    ->from(['mp_userdata' => 'marketplace_userdata'], 'seller_code')
                    ->where('seller_id=?', (int)$sellerId)
                    ->limit(1);
                $sellerCode = $conn->fetchOne($sellerCodeQuery);
                if($sellerCode == 'Z0081'){*/
                if ($ticketEdenred == $product->getAttributeSetId()) {
                    /**
                     * Load stock config
                     */
                    $stockData = $this->stockRegistry->getStockItem($product->getId());
                    if($stockData->getUseConfigMaxSaleQty() || $stockData->getMaxSaleQty() > 10){
                        $stockData->setUseConfigMaxSaleQty(0);
                        $stockData->setMaxSaleQty(10);
                        $this->stockRegistry->updateStockItemBySku($product->getSku(), $stockData);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->mpHelper->logDataInLogger(
                "Observer_CatalogProductSaveAfterObserver execute : ".$e->getMessage()
            );
            $this->messageManager->addError($e->getMessage());
        }
    }
    /**
     * Get Attribute Set ID by Name
     *
     * @param string $setName
     * @return int|null
     */
    private function getAttributeSetIdByName(string $setName): ?int
    {
        // 4 = catalog_product entity type
        $collection = $this->attributeSetCollectionFactory->create();
        $collection->addFieldToFilter('attribute_set_name', $setName)
                   ->addFieldToFilter('entity_type_id', 4);

        $attributeSet = $collection->getFirstItem();

        return $attributeSet->getAttributeSetId() ?: null;
    }
}
