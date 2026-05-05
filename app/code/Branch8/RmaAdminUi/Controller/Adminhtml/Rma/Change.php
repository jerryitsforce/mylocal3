<?php
declare(strict_types=1);

namespace Branch8\RmaAdminUi\Controller\Adminhtml\Rma;

use Branch8\Rma\Helper\Data;
use Branch8\Rma\Helper\Email;
use Branch8\Rma\Helper\RmaActions;
use Branch8\Rma\Model\Rma\Status;
use Branch8\Sales\Helper\Order\UpdateOrderStatus;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\OrderFactory;
use Branch8\RmaAdminUi\Helper\Logger as CustomLogger;

class Change extends \Magento\Backend\App\Action implements HttpPostActionInterface
{
    private Data $mpRmaHelper;

    private CustomLogger $logger;

    private RmaActions $rmaActions;

    private UpdateOrderStatus $updateOrderStatus;


    /**
     * @param Context $context
     * @param Data $helper
     * @param RmaActions $rmaActions
     * @param UpdateOrderStatus $updateOrderStatus
     * @param CustomLogger $logger
     * @param OrderFactory $orderFactory
     * @param Email $emailHelper
     */
    public function __construct(
        Context $context,
        Data $helper,
        RmaActions $rmaActions,
        UpdateOrderStatus $updateOrderStatus,
        CustomLogger $logger,
    ) {
        $this->rmaActions = $rmaActions;
        $this->mpRmaHelper = $helper;
        $this->logger = $logger;
        $this->updateOrderStatus = $updateOrderStatus;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $indexRedirect = $this->resultRedirectFactory
            ->create()
            ->setPath('mprmasystem/rma/index');
        if (!$this->getRequest()->isPost()) {
            return $indexRedirect;
        }
        $data = $this->getRequest()->getParams();
        $rmaId = $this->mpRmaHelper->decrypt($data['rma_id']);
        $editRedirect = $this->resultRedirectFactory
            ->create()
            ->setPath(
                'mprmasystem/rma/edit',
                ['id' => $rmaId, 'back' => null, '_current' => true]
            );
        if (!is_numeric($rmaId)) {
            $this->messageManager->addError(__("Invalid rma Id."));
            return $indexRedirect;
        }
        $isCustomer = $this->mpRmaHelper->getCustmerByRmaId($rmaId);
        if (!$isCustomer) {
            $this->messageManager->addError(__("Customer not exists"));
            return $editRedirect;
        }

        try {

            $this->validatePost($data);

            $changedStatus = (int)$data['changed_status'];
            $extraParams = [];
            if (in_array($changedStatus, Status::declinedStatus())) {
                $extraParams = [
                    'decline_reason_id' => $data['decline_reason_id'],
                    'decline_reason_detail' => $data['decline_reason_detail'],
                ];
            }

            if ($changedStatus == Status::REPLACE_TO_RETURN) {
                $this->rmaActions->changeResolutionType($rmaId, $this->mpRmaHelper::RESOLUTION_REFUND);
            }

            if ($changedStatus == Status::RETURN_TO_REPLACE) {
                $this->rmaActions->changeResolutionType($rmaId, $this->mpRmaHelper::RESOLUTION_REPLACE);
            }

            if ($changedStatus == Status::REPLACE_AGAIN) {
                $extraParams['is_replace_again'] = true;
            }

            if (in_array($changedStatus, Status::canCancelTicket())) {
                $this->rmaActions->cancelTicket($rmaId);
            }

            // If reject replace again action, just document this action but not change status
            if ($changedStatus == Status::REJECT_REPLACE_AGAIN) {
                $this->rmaActions->saveActionRecord($changedStatus, $rmaId);
                return $editRedirect;
            }

            if (in_array($changedStatus, 
                [Status::RETURN_APPLY_CANCEL, Status::REPLACE_APPLY_CANCEL])) {
                    
                    $this->updateOrderStatus->cancelRmaApplication($rmaId);
    
                    $items = $this->mpRmaHelper->getRmaActions()->getRmaItemCollection($rmaId);
                    
                    foreach ($items as $item) {
                        $this->updateOrderStatus
                            ->setBackToFlowStatusBeforeRma($item->getId());
                    }
                    
                    return $editRedirect;

            }

            $status = $this->rmaActions->changeStatusByAdmin(
                $rmaId,
                $changedStatus,
                $extraParams
            );
            /**
             * @var $singleItem \Magento\Sales\Model\Order\Item
             */
            $items = $this->rmaActions->getRmaItemCollection($rmaId);
            foreach ($items as $singleItem) {
                $this->updateOrderStatus->updateItemStatusById(
                    $singleItem->getItemId(),
                    $status,
                    $singleItem->getOrderId(),
                    false,
                    null,
                    false
                );
            }

            foreach ($items as $singleItem) {
                $this->updateOrderStatus->addItemStatusRecord(
                    $singleItem->getOrderId(),
                    $singleItem,
                    $status
                );
            }

            $this->_eventManager->dispatch('branch8_rma_status_change_after', ['rma_id' => $rmaId, 'new_status' => $changedStatus, 'extra_params' => $extraParams]);
            $this->_eventManager->dispatch(
                'return_exchange_change',
                [
                    'rma_id' => $rmaId
                ]
            );
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $exception) {
            $this->logger->critical('Error When Update RMA');
            $this->logger->info($exception->getTraceAsString());
            $this->messageManager->addErrorMessage(__('Unknown Error'));
        }
        return $editRedirect;
    }

    /**
     * @param $params
     * @return void
     * @throws InputException
     */
    private function validatePost($params)
    {
        $declineStatus = Status::declinedStatus();
        $changedStatus = (int)$params['changed_status'];
        if (in_array($changedStatus, $declineStatus)
            && (
                empty($params['decline_reason_id'])
                || empty($params['decline_reason_detail'])
            )
        ) {
            throw new InputException(__('Invalid declined reasons fields '));
        }
    }
}
