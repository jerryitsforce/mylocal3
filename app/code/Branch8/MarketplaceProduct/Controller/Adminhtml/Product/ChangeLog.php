<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Controller\Adminhtml\Product;

use Branch8\MarketplaceProduct\Api\Data\ProductVersionInterface;
use Branch8\MarketplaceProduct\Model\MarketplaceProductManagement;
use Branch8\MarketplaceProduct\Model\ResourceModel\GetProductLogEntryByProductId;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\Controller\Result\Forward;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Webkul\Marketplace\Model\Product;

class ChangeLog extends Action implements HttpGetActionInterface
{
    /**
     * @inheritdoc
     */
    const ADMIN_RESOURCE = 'Branch8_MarketplaceProduct::product_approval_management_change_log';

    /**
     * @var Registry
     */
    private Registry $coreRegistry;

    /**
     * @var MarketplaceProductManagement
     */
    private MarketplaceProductManagement $marketplaceProductManagement;

    /**
     * @var GetProductLogEntryByProductId
     */
    private GetProductLogEntryByProductId $getProductLogEntryByProductId;

    /**
     * ChangeLog constructor.
     *
     * @param Context $context
     * @param Registry $coreRegistry
     * @param MarketplaceProductManagement $marketplaceProductManagement
     * @param GetProductLogEntryByProductId $getProductLogEntryByProductId
     */
    public function __construct(
        Context                       $context,
        Registry                      $coreRegistry,
        MarketplaceProductManagement  $marketplaceProductManagement,
        GetProductLogEntryByProductId $getProductLogEntryByProductId
    ) {
        parent::__construct($context);
        $this->coreRegistry = $coreRegistry;
        $this->marketplaceProductManagement = $marketplaceProductManagement;
        $this->getProductLogEntryByProductId = $getProductLogEntryByProductId;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $mpProductId = (int)$this->getRequest()->getParam('mp_product_id');

        try {
            $mpProduct = $this->marketplaceProductManagement->getById($mpProductId);
            $productId = $mpProduct->getData('mageproduct_id');
        } catch (NoSuchEntityException $e) {
            /** @var Forward $resultForward */
            $resultForward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
            $resultForward->forward('noroute');
            return $resultForward;
        }

        $isProcessed = (int)$mpProduct->getData('status') !== Product::STATUS_PENDING;
        if ($isProcessed) {
            $logEntry = [$this->getProductLogEntryByProductId->execute((int)$productId, $isProcessed)];
        } else {
            $logEntry = $this->getProductLogEntryByProductId->executeAll((int)$mpProductId, $isProcessed,'main_table.entity_id');
        }
        $this->coreRegistry->register('current_log_entry', $logEntry);

        $this->_view->loadLayout();
        $this->_view->getPage()->getConfig()->getTitle()
            ->prepend(__('Log Entry for Product #%1', $productId ?? null));
        $this->_view->renderLayout();
    }


    /**
     * Check for is allowed.
     *
     * @return boolean
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Branch8_MarketplaceProduct::product_approval');
    }
}
