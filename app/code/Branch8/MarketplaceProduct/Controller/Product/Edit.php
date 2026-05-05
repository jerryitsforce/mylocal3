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

namespace Branch8\MarketplaceProduct\Controller\Product;

use Magento\Customer\Model\Session;
use Magento\Downloadable\Helper\Download;
use Magento\Downloadable\Helper\File;
use Magento\Downloadable\Model\LinkFactory;
use Magento\Downloadable\Model\SampleFactory;
use Branch8\MarketplaceProduct\Api\ProductTempDataRepositoryInterface;
use Magento\Customer\Model\Url as CustomerUrl;
use \Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
use Webkul\Marketplace\Controller\Product\Builder;
use Webkul\Marketplace\Controller\Product\Webkul;
use Webkul\Marketplace\Helper\Data as HelperData;
use Webkul\Marketplace\Helper\Notification as NotificationHelper;
use Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory;

/**
 * Webkul Marketplace Product Edit Controller.
 */
class Edit extends \Webkul\Marketplace\Controller\Product\Edit
{
    /**
     * @var ProductTempDataRepositoryInterface
     */
    protected ProductTempDataRepositoryInterface $productTempDataRepository;

    /**
     * @param Context $context
     * @param Builder $productBuilder
     * @param PageFactory $resultPageFactory
     * @param Session $customerSession
     * @param ProductTempDataRepositoryInterface $productTempDataRepository
     * @param CustomerUrl|null $customerUrl
     * @param HelperData|null $helper
     * @param NotificationHelper|null $notificationHelper
     * @param CollectionFactory|null $productCollection
     * @param SampleFactory|null $sample
     * @param File|null $fileHelper
     * @param Download|null $downloadHelper
     * @param LinkFactory|null $linkModel
     */
    public function __construct(
        Context                            $context,
        Builder                            $productBuilder,
        PageFactory                        $resultPageFactory,
        Session                            $customerSession,
        ProductTempDataRepositoryInterface $productTempDataRepository,
        CustomerUrl                        $customerUrl = null,
        HelperData                         $helper = null,
        NotificationHelper                 $notificationHelper = null,
        CollectionFactory                  $productCollection = null,
        SampleFactory                      $sample = null,
        File                               $fileHelper = null,
        Download                           $downloadHelper = null,
        LinkFactory                        $linkModel = null
    ){
        parent::__construct(
            $context,
            $productBuilder,
            $resultPageFactory,
            $customerSession,
            $customerUrl,
            $helper,
            $notificationHelper,
            $productCollection,
            $sample,
            $fileHelper,
            $downloadHelper,
            $linkModel
        );
        $this->productTempDataRepository = $productTempDataRepository;
    }

    /**
     * Seller Product Edit Action.
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        if ($this->getRequest()->isXmlHttpRequest() ||
            !str_contains($this->getRequest()->getHeader('Accept'), 'text/html')) {
            return;
        }
        $helper = $this->helper;
        $isPartner = $helper->isSeller();
        if ($isPartner == 1) {
            $productId = (int)$this->getRequest()->getParam('id');
            $tempId = (int)$this->getRequest()->getParam('temp_id');
            $delDraft = (int)$this->getRequest()->getParam('del_draft');
            $rightSeller = $helper->isRightSeller($productId);
            if (!$rightSeller && $tempId) {
                try {
                    $tempData = $this->productTempDataRepository->getById($tempId);
                    if ($tempData->getId()) {
                        $rightSeller = true;
                    }
                }catch (NoSuchEntityException $e) {
                }
            } elseif ($productId && $delDraft) {
                try {
                    $tempData = $this->productTempDataRepository->get($productId);
                    $this->productTempDataRepository->delete($tempData);
                }catch (NoSuchEntityException $e) {
                }
            }
            if ($rightSeller) {
                $product = $this->productBuilder->build(
                    $this->getRequest()->getParams(),
                    $helper->getCurrentStoreId()
                );

                if ($productId && !$product->getId()) {
                    $this->messageManager->addError(
                        __('This product no longer exists.')
                    );
                    /*
                     * @var \Magento\Backend\Model\View\Result\Redirect
                     */
                    $resultRedirect = $this->resultRedirectFactory->create();

                    return $resultRedirect->setPath(
                        '*/*/productlist',
                        ['_secure' => $this->getRequest()->isSecure()]
                    );
                }
                if ($productId) {
                    /** @var \Magento\Framework\View\Result\Page $resultPage */
                    $resultPage = $this->_resultPageFactory->create();
                    if ($helper->getIsSeparatePanel()) {
                        $resultPage->addHandle('marketplace_layout2_product_edit');
                    }
                    $resultPage->getConfig()->getTitle()->set(
                        __('Edit Product')
                    );

                    $collectionFactory = $this->productCollection;
                    /**
                     * update notification for products
                     */
                    $collection = $collectionFactory->create()
                        ->addFieldToFilter(
                            'mageproduct_id',
                            $productId
                        )->addFieldToFilter(
                            'seller_pending_notification',
                            1
                        );
                    if ($collection->getSize()) {
                        $type = \Webkul\Marketplace\Model\Notification::TYPE_PRODUCT;
                        $this->notificationHelper->updateNotificationCollection(
                            $collection,
                            $type
                        );
                    }

                    return $resultPage;
                } elseif ($tempId && $product->getTypeId()) {
                    /** @var \Magento\Framework\View\Result\Page $resultPage */
                    $resultPage = $this->_resultPageFactory->create();
                    if ($helper->getIsSeparatePanel()) {
                        $resultPage->addHandle('marketplace_layout2_product_edit');
                    }
                    $resultPage->getConfig()->getTitle()->set(
                        __('Edit Product')
                    );

                    return $resultPage;
                } else {
                    return $this->resultRedirectFactory->create()->setPath(
                        '*/*/add',
                        ['_secure' => $this->getRequest()->isSecure()]
                    );
                }
            } else {
                return $this->resultRedirectFactory->create()->setPath(
                    'marketplace/product/productlist',
                    ['_secure' => $this->getRequest()->isSecure()]
                );
            }
        } else {
            return $this->resultRedirectFactory->create()->setPath(
                'marketplace/account/becomeseller',
                ['_secure' => $this->getRequest()->isSecure()]
            );
        }
    }
}
