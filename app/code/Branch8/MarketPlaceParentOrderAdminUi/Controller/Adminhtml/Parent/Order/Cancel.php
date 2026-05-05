<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Controller\Adminhtml\Parent\Order;

use Branch8\MarketPlaceParentOrderAdminUi\Controller\Adminhtml\ParentOrder;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;

class Cancel extends ParentOrder implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Branch8_MarketPlaceParentOrderAdminUi::cancel';

    /**
     * Cancel order
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $parentOrder = $this->_initParentOrder();
        if (!$this->isValidPostRequest()
            || empty($parentOrder)
            || !$parentOrder->getId()
            || !$this->parentOrderManagement->canCancel($parentOrder)
        ) {
            $this->messageManager->addErrorMessage(__('You have not canceled the item.'));
            return $resultRedirect->setPath('sales/*/');
        }
        try {
            $this->parentOrderManagement->cancel($parentOrder);
            $this->messageManager->addSuccessMessage(__('You canceled the order.'));
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('You have not canceled the item.'));
            $this->_objectManager->get(\Branch8\MarketPlaceParentOrderAdminUi\Helper\Logger::class)->critical($e);
        }
        return $resultRedirect->setPath('sales/parent_order/view', ['id' => $parentOrder->getId()]);
    }
}
