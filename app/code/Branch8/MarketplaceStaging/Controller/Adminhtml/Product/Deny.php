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

namespace Branch8\MarketplaceStaging\Controller\Adminhtml\Product;

use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Webkul\Marketplace\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\Indexer\Product\Price\Processor;
use Webkul\Marketplace\Model\ProductFactory;
use Webkul\Marketplace\Helper\Data as MpHelper;
use Webkul\Marketplace\Helper\Notification as NotificationHelper;
use Magento\Catalog\Model\CategoryFactory;
use Webkul\Marketplace\Helper\Email as MpEmailHelper;

/**
 * Class Deny used to deny the product.
 */
class Deny extends \Webkul\Marketplace\Controller\Adminhtml\Product\Deny
{
    protected $b8SubAccountHelper;
    public function __construct(
        Context $context,
        Filter $filter,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        CollectionFactory $collectionFactory,
        Processor $productPriceIndexerProcessor,
        \Branch8\MarketplaceSubAccount\Helper\Data $b8SubAccountHelper,
        ProductFactory $productModel = null,
        \Magento\Catalog\Model\Product\Action $productAction = null,
        MpHelper $mpHelper = null,
        NotificationHelper $notificationHelper = null,
        CategoryFactory $categoryFactory = null,
        \Magento\Catalog\Model\ProductFactory $productFactory = null,
        \Magento\Customer\Model\CustomerFactory $customerModel = null,
        MpEmailHelper $mpEmailHelper = null
    ) {
        parent::__construct($context, $filter, $date, $dateTime, $storeManager, $productRepository,
        $collectionFactory, $productPriceIndexerProcessor, $productModel, $productAction, $mpHelper,
        $notificationHelper, $categoryFactory, $productFactory, $customerModel, $mpEmailHelper);
        $this->b8SubAccountHelper = $b8SubAccountHelper;
    }
    /**
     * Execute action.
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     *
     * @throws \Magento\Framework\Exception\LocalizedException|\Exception
     */
    public function execute()
    {
        $data = $this->getRequest()->getParams();
        $collection = $this->productModel->create()
            ->getCollection()
            ->addFieldToFilter('mageproduct_id', $data['mageproduct_id'])
            ->addFieldToFilter('seller_id', $data['seller_id']);
        if ($collection->getSize()) {
            $productIds = [$data['mageproduct_id']];
            $allStores = $this->_storeManager->getStores();
            $status = \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED;
            $sellerProductStatus = \Webkul\Marketplace\Model\Product::STATUS_DISABLED;

            $sellerProduct = $this->productModel->create()->getCollection();

            $conditionData = "`mageproduct_id`=".$data['mageproduct_id'];

            $sellerProduct->setProductData(
                $conditionData,
                ['status' => $sellerProductStatus, 'seller_pending_notification' => 1]
            );
            foreach ($allStores as $eachStoreId => $storeId) {
                $this->productAction->updateAttributes($productIds, ['status' => $status], $storeId);
            }

            $this->productAction->updateAttributes($productIds, ['status' => $status], 0);

            $this->_productPriceIndexerProcessor->reindexList($productIds);

            $catagoryModel = $this->categoryFactory->create();

            $helper = $this->mpHelper;

            $id = 0;

            foreach ($collection as $item) {
                $id = $item->getId();
                $this->notificationHelper->saveNotification(
                    \Webkul\Marketplace\Model\Notification::TYPE_PRODUCT,
                    $id,
                    $data['mageproduct_id']
                );
            }

            $model = $this->productFactory->create()->load($data['mageproduct_id']);

            $catarray = $model->getCategoryIds();
            $categoryname = '';
            foreach ($catarray as $keycat) {
                $categoriesy = $catagoryModel->load($keycat);
                if ($categoryname == '') {
                    $categoryname = $categoriesy->getName();
                } else {
                    $categoryname = $categoryname.','.$categoriesy->getName();
                }
            }
            $allStores = $this->_storeManager->getStores();

            $pro = $this->productModel->create()->load($id);
            $seller = $this->customerModel->create()->load($data['seller_id']);
            if (isset($data['notify_seller']) && $data['notify_seller'] == 1) {
                $helper = $this->mpHelper;
                $adminStoreEmail = $helper->getAdminEmailId();
                $adminEmail = $adminStoreEmail ? $adminStoreEmail : $helper->getDefaultTransEmailId();
                $adminUsername = $helper->getAdminName();

                $emailTempVariables['myvar1'] = $seller->getName();
                $emailTempVariables['myvar2'] = $data['product_deny_reason'];
                $emailTempVariables['myvar3'] = $model->getName();
                $emailTempVariables['myvar4'] = $categoryname;
                $emailTempVariables['myvar5'] = $model->getDescription();
                $emailTempVariables['myvar6'] = $model->getPrice();
                $senderInfo = [
                        'name' => $adminUsername,
                        'email' => $adminEmail,
                    ];
                $receiverInfo = [
                    'name' => $seller->getName(),
                    'email' => $seller->getEmail(),
                ];
                $this->mpEmailHelper->sendProductDenyMail(
                    $emailTempVariables,
                    $senderInfo,
                    $receiverInfo
                );
                /**
                 * Send mail to Sub account
                 */
                $this->b8SubAccountHelper->sendProductDenyMailToSubAccount($data['seller_id'], $emailTempVariables, $senderInfo);
            }

            $this->_eventManager->dispatch(
                'mp_deny_product',
                ['product' => $pro, 'seller' => $seller]
            );

            $this->messageManager->addSuccess(__('Product has been Denied.'));
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Check for is allowed.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Webkul_Marketplace::product');
    }
}
