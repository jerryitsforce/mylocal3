<?php

namespace Branch8\MarketplaceProduct\Controller\Adminhtml\ManagerProduct;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Webkul\Marketplace\Model\ProductFactory;

class Index extends Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Backend\Model\View\Result\Page
     */
    protected $resultPage;

    /**
     * @var ProductFactory
     * @param PageFactory   $resultPageFactory
     */
    protected $productModel;

    /**
     * @param Context           $context
     * @param PageFactory       $resultPageFactory
     * @param ProductFactory    $productModel
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ProductFactory $productModel = null
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->productModel = $productModel ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(ProductFactory::class);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $productCollection = $this->productModel->create()
            ->getCollection()
            ->addFieldToFilter('admin_pending_notification', ['neq' => 0]);
        if ($productCollection->getSize()) {
            $this->_updateNotification($productCollection);
        }
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Branch8_MarketplaceProduct::product');
        $resultPage->getConfig()->getTitle()->prepend(__('Manager Product Approval'));
        return $resultPage;
    }

    /**
     * Updated all notification as read.
     *
     * @param   \Webkul\Marketplace\Model\Product $collection
     */
    protected function _updateNotification($collection)
    {
        foreach ($collection as $value) {
            $value->setAdminPendingNotification(0);
            $value->setId($value->getEntityId())->save();
        }
    }

    /**
     * @inheritDoc
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Branch8_MarketplaceProduct::manager_product_approval');
    }
}
