<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Branch8\Sales\Controller\Order;

use Branch8\HelpDesk\Controller\AbstractController;
use Branch8\Sales\Helper\Order\UpdateOrderStatus;
use Magento\Framework\App\Action\HttpPostActionInterface;
use \Magento\Framework\App\Action\Context;

class CancelRma extends AbstractController implements HttpPostActionInterface
{
    protected $updateOrderStatus;

    public function __construct(
        Context $context,
        UpdateOrderStatus $updateOrderStatus
    ) {
        $this->updateOrderStatus = $updateOrderStatus;
        parent::__construct($context);
    }
    /**
     * Update Order Status
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        if (!$this->getRequest()->isPost()) {
            $this->messageManager->addError(__('Something went wrong.'));
            return;
        }

        $postData = $this->getRequest()->getParams();

        if (!empty($postData)) {
            try {
                $this->updateOrderStatus->cancelRmaApplication(
                    $postData['rma_id']
                );

                $this->updateOrderStatus->setBackToFlowStatusBeforeRma(
                    $postData['item_id']
                );
            } catch (\Exception $e) {
                $this->messageManager->addError(__('There is some problem as updating status.'));
                $this->messageManager->addError(__($e->getMessage()));
            }

            return $this->resultRedirectFactory->create()->setPath('sales/parentOrder/history');
        } else {
            $this->messageManager->addError(__('Something went wrong.'));
        }

    }
}
