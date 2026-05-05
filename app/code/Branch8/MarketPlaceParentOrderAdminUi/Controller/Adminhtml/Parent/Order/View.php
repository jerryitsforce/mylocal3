<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Controller\Adminhtml\Parent\Order;

use Branch8\MarketPlaceParentOrderAdminUi\Controller\Adminhtml\ParentOrder;
use Magento\Backend\App\Action;

class View extends ParentOrder
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Branch8_MarketPlaceParentOrderAdminUi::actions_view';

    /**
     * View order detail
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        /**
         * @var $parentOrder \Branch8\MarketPlaceParentOrder\Model\ParentOrder
         */
        $parentOrder = $this->_initParentOrder();
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($parentOrder) {
            try {
                $resultPage = $this->_initAction();
                $resultPage->getConfig()->getTitle()->prepend(__('Orders'));
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $this->messageManager->addErrorMessage(__('Exception occurred during order load'));
                $resultRedirect->setPath('sales/parent_order/index');
                return $resultRedirect;
            }
            $resultPage->getConfig()->getTitle()->prepend(sprintf("#%s",
                    $parentOrder->getExtensionAttributes()->getDetail()->getIncrementId())
            );
            $resultPage->getLayout()->getUpdate()->addHandle(
                'sales_order_item_price'
            );
            return $resultPage;
        }
        $resultRedirect->setPath('sales/*/');
        return $resultRedirect;
    }
}
