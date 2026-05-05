<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Controller\Adminhtml\Parent\Order;

use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderRepositoryInterface;
use Branch8\MarketPlaceParentOrderAdminUi\Controller\Adminhtml\ParentOrder;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;
use Branch8\MarketPlaceParentOrderAdminUi\Helper\Logger as LoggerInterface;

class UnHold extends ParentOrder implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Branch8_MarketPlaceParentOrderAdminUi::unHold';

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
            || !$this->parentOrderManagement->canUnHold($parentOrder)
        ) {
            $this->messageManager->addErrorMessage(__('You can not un-hold this order.'));
            return $resultRedirect->setPath('sales/*/');
        }
        try {
            $this->parentOrderManagement->unHold($parentOrder);
            $this->messageManager->addSuccessMessage(__('You un-hold the order.'));
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('You have not un-hold the item.'));
            $this->_objectManager->get(\Branch8\MarketPlaceParentOrderAdminUi\Helper\Logger::class)->critical($e);
        }
        return $resultRedirect->setPath('sales/*/view', ['id' => $parentOrder->getId()]);
    }
}
