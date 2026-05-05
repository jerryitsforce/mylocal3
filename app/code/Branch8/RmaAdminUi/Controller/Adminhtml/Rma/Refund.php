<?php

namespace Branch8\RmaAdminUi\Controller\Adminhtml\Rma;

use Branch8\Rma\Helper\Data;
use Branch8\Rma\Helper\RmaActions;
use Branch8\Rma\Model\Rma\Status;
use Branch8\Sales\Helper\Order\UpdateOrderStatus;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Branch8\RmaAdminUi\Helper\Logger as CustomLogger;

class Refund extends \Magento\Backend\App\Action implements HttpPostActionInterface
{
    private Data $mpRmaHelper;

    private CustomLogger $logger;

    private RmaActions $rmaActions;

    private UpdateOrderStatus $updateOrderStatus;

    /**
     * @var \Magento\Customer\Model\Url
     */
    protected $url;
    /**
     * @var \Webkul\MpRmaSystem\Model\DetailsFactory
     */
    protected $details;

    /***
     * @param \Magento\Backend\App\Action\Context $context
     * @param Data $helper
     * @param RmaActions $rmaActions
     * @param UpdateOrderStatus $updateOrderStatus
     * @param \Webkul\MpRmaSystem\Model\DetailsFactory $details
     * @param CustomLogger $logger
     */
    public function __construct(
        \Magento\Backend\App\Action\Context      $context,
        Data                                     $helper,
        RmaActions                               $rmaActions,
        UpdateOrderStatus                        $updateOrderStatus,
        \Webkul\MpRmaSystem\Model\DetailsFactory $details,
        CustomLogger                             $logger
    )
    {
        $this->rmaActions = $rmaActions;
        $this->mpRmaHelper = $helper;
        $this->logger = $logger;
        $this->details = $details;
        $this->updateOrderStatus = $updateOrderStatus;
        parent::__construct($context);
    }

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
        $negative = 0;
        $totalPrice = 0;
        $totalPoint = 0;
        $partial_amount = 0;
        $sellerShippingAmount = 0;
        $productDetails = $this->mpRmaHelper->getRmaProductDetails($rmaId);
        $IsCustomer = $this->mpRmaHelper->getCustmerByRmaId($rmaId);
        $items = $this->rmaActions->getRmaItemCollection($rmaId);
        $stock = isset($data['back_to_stock']) ? 1 : 0;
        $doOffline = $data['do_offline'];
        $invoiceId = $data['invoice_id'];
        $totalShippingAmount = $data['totalShippingAmount'];

        if ($invoiceId) {
            $myinvoiceId = explode(',', $invoiceId);
            foreach ($myinvoiceId as $invoiceId) {
                if ($invoiceId) {
                    $invoiceId = $invoiceId;
                }
            }
        }
        if (!$invoiceId) {
            $this->messageManager->addErrorMessage(__("There is no invoice data, please contact admin."));
            return $editRedirect;
        }

        if (!is_numeric($data['partial_amount'])) {
            $this->messageManager->addErrorMessage(__("You have enter wrong amount format"));
            return $editRedirect;
        }

        if (!$IsCustomer) {
            $this->messageManager->addErrorMessage(__("Customer not exists"));
            return $this->resultRedirectFactory
                ->create()
                ->setPath(
                    'mprmasystem/seller/rma',
                    ['id' => $rmaId, 'back' => null, '_current' => true]
                );
        }

        if ($productDetails->getSize()) {
            foreach ($productDetails as $item) {
                $totalPrice += $this->mpRmaHelper->getItemFinalPrice($item);
                $totalPoint = $totalPoint + (int)$item->getRowTotalPointUsed();
            }
        }

        if ($data['payment_type'] == 2) {
            $partial_amount = str_replace(',', '', $data['partial_amount']);
            $negative = $totalPrice - $partial_amount;
        }

        if (isset($data['seller_shipping'])) {
            $sellerShippingAmount = $data['seller_shipping'];
            $totalPrice = (float)$totalPrice + (float)$sellerShippingAmount;
        }

        $rma = $this->details->create()->load($rmaId);

        $allItemsReturned = $this->mpRmaHelper->isAllItemUnderReturned(
            $this->mpRmaHelper->getOrder($rma->getOrderId()),
            count($items));

        if ($allItemsReturned) {
            $sellerShippingAmount = $totalShippingAmount;
            $totalPrice           = (float) $totalPrice + (float) $sellerShippingAmount;
        }

        $refundedAmount = $totalPrice - $negative;

        // save return to stock option
        $rmaData = [
            'return_to_stock' => $stock 
        ];

        $rma->addData($rmaData)->setId($rmaId)->save();

        $order = $this->mpRmaHelper->getOrder($data['id']);
        $stock = $order->getEcpayInvoiceCustomerIdentifier() ? false: $stock;

        try {
            // add total point as negative
            $negative = $negative + $totalPoint;
            $data = [
                'rma_id' => $rmaId,
                'negative' => $negative,
                'do_offline' => $doOffline,
                'invoice_id' => $invoiceId,
                'back_to_stock' => $stock,
                'seller_shipping_amount' => $sellerShippingAmount,
                'admin_shipping_amount' => 0,
            ];

            $result = $this->mpRmaHelper->createCreditMemo($data);
            if ($result['error']) {
                $this->messageManager->addErrorMessage($result['msg']);
            } else {
                $this->messageManager->addSuccessMessage($result['msg']);
                $rma = $this->details->create()->load($rmaId);
                $orderId = $rma->getOrderId();
                // update status: if natural person AND no manual invoice: refund processing else financial review
                $order = $this->mpRmaHelper->getOrder($orderId);
                $taxId = $rma->getCustomerTaxIdNumber();
                $hasManualInvoice = (bool) $order->getIsManualInvoice();
                $status = (is_null($taxId) && !$hasManualInvoice) ? Status::RETURN_REFUND_PROCESSING : Status::RETURN_FINANCIAL_REVIEW_PROCESSING;
                $rmaData = [
                    'status' => $status,
                    'memo_id' => $result['memo_id'],
                    'refunded_amount' => $refundedAmount,
                    'refunded_point' => $totalPoint,
                ];
                $rma->addData($rmaData)->setId($rmaId)->save();
                $this->mpRmaHelper->updateMpOrder($orderId, $result['memo_id']);
                $status = $this->rmaActions->changeStatusByAdmin($rmaId, $status);
                foreach ($items as $item) {
                    $this->updateOrderStatus->addItemStatusRecord(
                        $item->getOrderId(), $item, $status
                    );

                    $item->setFlowStatus($status);
                    $item->save();
                }

                $this->mpRmaHelper->updateItemRefundColumnAfterIssueCreditMemo(
                    $result['memo_id'],
                    $status
                );

                try {
                    $this->mpRmaHelper->sendUpdateRmaEmail($data);
                } catch (\Exception $e) {
                    $this->messageManager->addErrorMessage(__($e->getMessage()));
                }
                $this->mpRmaHelper->updateRmaItemQtyStatus($rmaId);
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
            return $editRedirect;
        }
        return $editRedirect;
    }
}
