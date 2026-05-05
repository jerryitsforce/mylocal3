<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Controller\Adminhtml\Parent\Order;

use Magento\Framework\App\ObjectManager;

class StartEdit extends AbstractCreate
{
    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $parentOrder = $this->_initParentOrder();
        $helper = $this->_getHelper();
        if (!$parentOrder) {
            $this->messageManager->addErrorMessage(__(
                'Parent order does not exist'
            ));
            return $resultRedirect->setPath('sales/parent_order/index');
        }
        if ($parentOrder) {
            try {
                $session = $this->_getSession();
                $session->clearStorage();
                $this->getCoreSession()->unsOldParentOrderId();
                $session->setCurrentAction('edit_parent_order');
                $session->setEditParentOrderFrom($parentOrder->getId());
                $session->setUseOldShippingMethod(true);
                $this->_getOrderCreateModel()->initFromParentOrder($parentOrder);
                $resultRedirect->setPath('sales/order_create/index');
                return $resultRedirect;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('You have not edit the item.'));
                $this->_objectManager->get(\Branch8\MarketPlaceParentOrderAdminUi\Helper\Logger::class)->critical($e);
            }
            return $resultRedirect->setPath('sales/parent_order/view', ['id' => $parentOrder->getId()]);
        }
        return $resultRedirect->setPath('sales/*/');
    }

    /**
     * @return \Magento\Framework\Session\SessionManager|mixed
     */
    private function getCoreSession()
    {
        return ObjectManager::getInstance()->get(\Magento\Framework\Session\SessionManager::class);
    }
}
