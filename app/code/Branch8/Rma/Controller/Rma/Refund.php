<?php
namespace Branch8\Rma\Controller\Rma;

use Branch8\Rma\Helper\Data;
use Branch8\Rma\Helper\RmaActions;
use Branch8\Rma\Model\Rma\Status;
use Branch8\Sales\Helper\Order\UpdateOrderStatus;
use Exception;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;

class Refund extends \Magento\Framework\App\Action\Action
{
    /**
     * Log option value for refund controller.
     */
    private const LOG_OPTION = 'Refund';
    /**
     * @var \Magento\Customer\Model\Url
     */
    protected $url;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $session;

    /**
     * @var \Branch8\Rma\Helper\Data
     */
    protected $mpRmaHelper;

    /**
     * @var \Webkul\MpRmaSystem\Model\DetailsFactory
     */
    protected $details;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /** @var \Branch8\Sales\Helper\Order\UpdateOrderStatus $updateOrderStatus */
    protected $updateOrderStatus;

    /**　@var \Branch8\Rma\Helper\RmaActions $rmaActions */
    protected $rmaActions;

    public function __construct(
        Context $context,
        \Magento\Customer\Model\Url $url,
        \Magento\Customer\Model\Session $session,
        \Branch8\Rma\Helper\Data $mpRmaHelper,
        \Webkul\MpRmaSystem\Model\DetailsFactory $details,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        UpdateOrderStatus $updateOrderStatus,
        RmaActions $rmaActions
    ) {
        $this->url               = $url;
        $this->session           = $session;
        $this->mpRmaHelper       = $mpRmaHelper;
        $this->details           = $details;
        $this->messageManager    = $messageManager;
        $this->updateOrderStatus = $updateOrderStatus;
        $this->rmaActions        = $rmaActions;
        parent::__construct($context);
    }

    /**
     * Check customer authentication.
     *
     * @param RequestInterface $request
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function dispatch(RequestInterface $request)
    {
        $loginUrl = $this->url->getLoginUrl();
        if (! $this->session->authenticate($loginUrl)) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
        }
        return parent::dispatch($request);
    }

    /**
     * Refund Action
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $data  = $this->getRequest()->getParams();
        $rmaId = $this->mpRmaHelper->decrypt($data['rma_id']);

        if (! is_numeric($rmaId)) {
            $this->messageManager->addError(__("Invalid rma Id."));
            return $this->resultRedirectFactory
                ->create()
                ->setPath(
                    'mprmasystem/seller/rma'
                );
        }

        $negative             = 0;
        $totalPrice           = 0;
        $totalPoint           = 0;
        $partial_amount       = 0;
        $sellerShippingAmount = 0;
        $productDetails       = $this->mpRmaHelper->getRmaProductDetails($rmaId);
        $IsCustomer           = $this->mpRmaHelper->getCustmerByRmaId($rmaId);
        $items                = $this->rmaActions->getRmaItemCollection($rmaId);
        $stock                = isset($data['back_to_stock']) ? 1 : 0;
        $doOffline            = $data['do_offline'];
        $invoiceId            = $data['invoice_id'];

        if ($invoiceId) {
            $myinvoiceId = explode(',', $invoiceId);
            foreach ($myinvoiceId as $invoiceId) {
                if ($invoiceId) {
                    $invoiceId = $invoiceId;
                }
            }
        }

        if (! $invoiceId) {
            $this->messageManager->addErrorMessage(__("There is no invoice data, please contact admin."));
            return $this->resultRedirectFactory
                ->create()
                ->setPath(
                    'mprmasystem/seller/rma',
                    ['id' => $rmaId, 'back' => null, '_current' => true]
                );
        }

        if (! is_numeric($data['partial_amount'])) {
            $this->messageManager->addErrorMessage(__("You have enter wrong amount format"));
            return $this->resultRedirectFactory
                ->create()
                ->setPath(
                    'mprmasystem/seller/rma',
                    ['id' => $rmaId, 'back' => null, '_current' => true]
                );
        }
        if (! $IsCustomer) {
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
                $totalPoint = $totalPoint + (int) $item->getRowTotalPointUsed();
            }
        }

        if ($data['payment_type'] == 2) {
            $partial_amount = str_replace(',', '', $data['partial_amount']);
            $negative       = $totalPrice - $partial_amount;
        }

        $rma = $this->details->create()->load($rmaId);

        $allItemsReturned = $this->mpRmaHelper->isAllItemUnderReturned(
            $this->mpRmaHelper->getOrder($rma->getOrderId()),
            count($items));

        if ($allItemsReturned) {
            $sellerShippingAmount = $data['total_refundable_shipping_amount'];
            $totalPrice           = (float) $totalPrice + (float) $sellerShippingAmount;
        }

        $refundedAmount = $totalPrice - $negative;

        // save return to stock option
        $rmaData = [
            'return_to_stock' => $stock,
        ];

        $rma->addData($rmaData)->setId($rmaId)->save();

        $order = $this->mpRmaHelper->getOrder($data['id']);
        $stock = $order->getEcpayInvoiceCustomerIdentifier() ? false : $stock;

        try {
            // add total point as negative
            $negative = $negative + $totalPoint;

            $data = [
                'rma_id'                 => $rmaId,
                'negative'               => $negative,
                'do_offline'             => $doOffline,
                'invoice_id'             => $invoiceId,
                'back_to_stock'          => $stock,
                'seller_shipping_amount' => $sellerShippingAmount,
                'admin_shipping_amount'  => 0,
            ];

            $result = $this->mpRmaHelper->createCreditMemo($data);

            if ($result['error']) {
                $this->messageManager->addErrorMessage($result['msg']);
            } else {
                $this->messageManager->addSuccessMessage($result['msg']);

                //$rma = $this->details->create()->load($rmaId);
                $orderId = $rma->getOrderId();

                // update status: if natural person AND no manual invoice: refund processing else financial review
                $order = $this->mpRmaHelper->getOrder($orderId);
                $taxId = $rma->getCustomerTaxIdNumber();
                $hasManualInvoice = (bool) $order->getIsManualInvoice();
                $status = (is_null($taxId) && !$hasManualInvoice) ? Status::RETURN_REFUND_PROCESSING : Status::RETURN_FINANCIAL_REVIEW_PROCESSING;

                $rmaData = [
                    'status'          => $status,
                    'memo_id'         => $result['memo_id'],
                    'refunded_amount' => $refundedAmount,
                    'refunded_point'  => $totalPoint,
                ];

                $rma->addData($rmaData)->setId($rmaId)->save();

                $this->mpRmaHelper->updateMpOrder($orderId, $result['memo_id']);

                $status = $this->rmaActions->changeStatusBySeller($rmaId, $status);

                foreach ($productDetails as $item) {
                    $this->updateOrderStatus->updateItemStatusById(
                        $item->getId(),
                        $status,
                        $item->getOrderId(),
                        false,
                        null,
                        true
                    );
                }

                $this->mpRmaHelper->updateItemRefundColumnAfterIssueCreditMemo(
                    $result['memo_id'],
                    $status
                );

                try {
                    $this->mpRmaHelper->sendUpdateRmaEmail($data);
                } catch (\Exception $e) {
                    \Magento\Framework\App\ObjectManager::getInstance()
                        ->get(\Branch8\Rma\Helper\Log::class)
                        ->exception($e, self::LOG_OPTION, __METHOD__);
                    $this->messageManager->addErrorMessage(__($e->getMessage()));
                }
                $this->mpRmaHelper->updateRmaItemQtyStatus($rmaId);
            }
        } catch (Exception $e) {
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Branch8\Rma\Helper\Log::class)
                ->exception($e, self::LOG_OPTION, __METHOD__, ['rma_id' => $rmaId]);
            $this->messageManager->addErrorMessage(__($e->getMessage()));
            return $this->resultRedirectFactory
                ->create()
                ->setPath(
                    'mprmasystem/seller/rma',
                    ['id' => $rmaId, 'back' => null, '_current' => true]
                );

        }

        return $this->resultRedirectFactory
            ->create()
            ->setPath(
                'mprmasystem/seller/rma',
                ['id' => $rmaId, 'back' => null, '_current' => true]
            );
    }
}
