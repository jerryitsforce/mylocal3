<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Controller\Adminhtml\Parent\Order;

use Branch8\MarketPlaceParentOrder\Model\Services\ReorderHelper;
use Branch8\MarketPlaceParentOrderAdminUi\Model\AdminOrder\Create;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;

class Reorder extends AbstractCreate implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Branch8_MarketPlaceParentOrderAdminUi::reorder';

    /**
     * Cancel order
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $helper = $this->_getHelper();
        /**
         * @var $parentOrder \Branch8\MarketPlaceParentOrder\Model\ParentOrder
         */
        $parentOrder = $this->_initParentOrder();
        if (!$this->isValidPostRequest()
            || !($helper->canReorder($parentOrder))
        ) {
            $this->messageManager->addErrorMessage(__(
                'You have not re-order this item.'
            ));
            return $resultRedirect->setPath('sales/*/');
        }
        if ($parentOrder) {
            try {
                $session = $this->_getSession();
                $session->clearStorage();
                $unavailableProducts = $helper->getUnavaiableProducts($parentOrder);
                if (count($unavailableProducts) > 0) {
                    foreach ($unavailableProducts as $sku) {
                        $this->messageManager->addErrorMessage(
                            sprintf('Product "%s" not found. This product is no longer available.', $sku)
                        );
                    }
                    $resultRedirect->setPath(
                        'sales/parent_order/view', ['id' => $parentOrder->getEntityId()]
                    );
                } else {
                    $session->setUseOldShippingMethod(true);
                    $session->setCurrentAction('reorder_parent_order');
                    $session->setReOrderParentOrderFrom($parentOrder->getId());
                    $this->_getOrderCreateModel()->initFromParentOrder($parentOrder);
                    $resultRedirect->setPath('sales/order_create/index');
                    return $resultRedirect;
                }
                //  $this->messageManager->addSuccessMessage(__('You canceled the order.'));
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('You have not re-order the item.'));
                $this->_objectManager->get(\Branch8\MarketPlaceParentOrderAdminUi\Helper\Logger::class)->critical($e);
            }
            return $resultRedirect->setPath('sales/parent_order/view', ['id' => $parentOrder->getId()]);
        }
        return $resultRedirect->setPath('sales/*/');
    }
}
