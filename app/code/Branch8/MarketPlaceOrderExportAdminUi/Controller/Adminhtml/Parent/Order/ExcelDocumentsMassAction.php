<?php

declare(strict_types=1);

namespace Branch8\MarketPlaceOrderExportAdminUi\Controller\Adminhtml\Parent\Order;

use Magento\Framework\Controller\ResultFactory;

abstract class ExcelDocumentsMassAction extends \Magento\Sales\Controller\Adminhtml\Order\AbstractMassAction
{
    /**
     * @var \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder\CollectionFactory
     */
    protected $orderCollectionFactory;
    /**
     * Execute action
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException|\Exception
     */
    public function execute()
    {
        try {
            $collection = $this->filter->getCollection($this->getOrderCollection()->create());
            return $this->massAction($collection);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath($this->redirectUrl);
        }
    }

    /**
     * Get Order Collection Factory
     *
     * @return \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder\CollectionFactory
     * @deprecated 100.1.3
     */
    private function getOrderCollection()
    {
        if ($this->orderCollectionFactory === null) {
            $this->orderCollectionFactory = \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder\CollectionFactory::class
            );
        }
        return $this->orderCollectionFactory;
    }
}
