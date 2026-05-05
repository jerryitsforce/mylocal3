<?php

namespace Branch8\RmaAdminUi\Controller\Adminhtml\Rma;

use Branch8\Rma\Helper\Data as RmaHelper;
use Branch8\Rma\Helper\RmaActions;
use Branch8\Rma\Model\SenderType;
use Branch8\Sales\Helper\Order\UpdateOrderStatus;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;
use Branch8\RmaAdminUi\Helper\Logger as CustomLogger;

class Shipping extends Action implements HttpPostActionInterface
{
    /**
     * @var RmaHelper
     */
    private RmaHelper $mpRmaHelper;

    /**
     * @var CustomLogger
     */
    private CustomLogger $logger;

    /**
     * @var RmaActions
     */
    private RmaActions $rmaActions;

    /**
     * @var UpdateOrderStatus
     */
    private UpdateOrderStatus $updateOrderStatus;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param RmaHelper $helper
     * @param RmaActions $rmaActions
     * @param UpdateOrderStatus $updateOrderStatus
     * @param CustomLogger $logger
     */
    public function __construct(
        Context           $context,
        RmaHelper         $helper,
        RmaActions        $rmaActions,
        UpdateOrderStatus $updateOrderStatus,
        CustomLogger      $logger,
    ) {
        parent::__construct($context);
        $this->rmaActions = $rmaActions;
        $this->mpRmaHelper = $helper;
        $this->logger = $logger;
        $this->updateOrderStatus = $updateOrderStatus;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->resultRedirectFactory
                ->create()
                ->setPath('mprmasystem/rma/index');
        }
        $data = $this->getRequest()->getParams();
        $rmaId = $this->mpRmaHelper->decrypt($data['rma_id']);
        $editRedirect = $this->resultRedirectFactory->create()
            ->setPath(
                'mprmasystem/rma/edit',
                ['id' => $rmaId, 'back' => null, '_current' => true]
            );
        if (!is_numeric($rmaId)) {
            $this->messageManager->addErrorMessage(__('Invalid RMA ID.'));
            return $this->resultRedirectFactory->create()
                ->setPath('mprmasystem/seller/rma');
        }

        try {
            $status = $this->rmaActions->updateReplaceShippingNumberBySellerOrAdmin(
                $rmaId,
                $data['shipping_number'],
                $this->getShippingCarrier($data),
                SenderType::TYPE_ADMIN
            );
            $item = $this->rmaActions->getRmaItemCollection($rmaId);
            foreach ($item as $singleItem) {
                $this->updateOrderStatus->addItemStatusRecord(
                    $singleItem->getOrderId(), $singleItem, $status);
                $singleItem->setFlowStatus($status);
                $singleItem->save();
            }
            $this->messageManager->addSuccessMessage(__('Update shipping successfully'));
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $exception) {
            $this->logger->info($exception->getTraceAsString());
            $this->messageManager->addErrorMessage(__('Something went wrong.'));
        }
        return $editRedirect;
    }

    /**
     * Returns shipping carrier from post data.
     *
     * @param array $data
     *
     * @return string
     */
    private function getShippingCarrier(array $data): string
    {
        $carrier = $data['shipping_carrier'] ?? false;
        if (!$carrier) {
            return '';
        }

        if ($carrier === '其他：自行填寫名稱') {
            $carrier = $data['logistics_provider_name'] ?? '';
        }

        return (string)$carrier;
    }
}
