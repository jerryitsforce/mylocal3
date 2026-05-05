<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Branch8\Hopes\Model;

use Branch8\Hopes\Helper\Logger as LoggerHelper;
use Branch8\Hopes\Helper\Log as HopesLog;
use Branch8\Hopes\Logger\Logger;
use Branch8\Hopes\Model\AbstractModel;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder;
use Branch8\Rma\Helper\Status as RmaStatus;
use Branch8\Sales\Helper\Order\UpdateOrderStatus as ItemStatus;
use Webkul\MpRmaSystem\Model\ResourceModel\Details\CollectionFactory as DetailsCollection;
use \Branch8\Rma\Helper\Config\StatusLabel;
use \Branch8\Rma\Helper\Data;
use \Magento\Catalog\Api\ProductRepositoryInterface;
use \Magento\Framework\Webapi\Rest\Request;
use \Magento\Framework\Webapi\Rest\Response;
use \Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Branch8\Hopes\Helper\Response\Message;
use Exception;

class UpdateReturnStatusManagement extends AbstractModel implements \Branch8\Hopes\Api\UpdateReturnStatusManagementInterface
{
    /**
     * Log type value used by admin multiselect.
     */
    private const LOG_TYPE = 'UpdateReturnStatusManagement';

    /** @var \Magento\Framework\Webapi\Rest\Request $request */
    public $request;
    
    /** @var Branch8\Hopes\Helper\Logger $logger */
    private $logger;
    
    /** @var /Branch8\Hopes\Helper\Logger $loggerhelper */
    private $loggerhelper;
    
    /** @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory */
    protected $orderCollectionFactory;
    
    /** @var array $result */
    public $result = [];
    
    /** @var array $configurable */
    public $configurable = [];
    
    /** @var \Magento\Catalog\Api\ProductRepositoryInterface */
    protected $productRepository;
    
    /** @var \Branch8\Rma\Helper\Data $rmaHelper */
    protected $rmaHelper;
    
    /** @var \Webkul\MpRmaSystem\Model\DetailsFactory */
    protected $details;
    
    /** @var \Branch8\Rma\Helper\Config\StatusLabel $statusLabel */
    protected $statusLabel;
    
    /**　@var \Branch8\Rma\Helper\Status $status */
    protected $status;
    
    /** @var \Branch8\Sales\Helper\Order\UpdateOrderStatus $itemStatus */
    protected $itemStatus;

    /**
     * @var HopesLog
     */
    private HopesLog $hopesLog;

    public function __construct(
        Logger $logger,
        LoggerHelper $loggerhelper,
        Request $request,
        CollectionFactory $orderCollectionFactory,
        Response $response,
        ParentOrder $parentOrder,
        ParentOrderFactory $parentOrderInterface,
        ProductRepositoryInterface $productRepository,
        Data $rmaHelper,
        DetailsCollection $details,
        StatusLabel $statusLabel,
        RmaStatus $status,
        ItemStatus $itemStatus,
        HopesLog $hopesLog
    ) {
        $this->rmaHelper = $rmaHelper;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->parentOrder = $parentOrder;
        $this->parentOrderInterface = $parentOrderInterface;
        $this->productRepository = $productRepository;
        $this->details = $details;
        $this->statusLabel = $statusLabel;
        $this->status = $status;
        $this->itemStatus = $itemStatus;
        $this->logger = $logger;
        $this->hopesLog = $hopesLog;
        parent::__construct($request, $logger, $loggerhelper, $response);
    }

    /**
     * {@inheritdoc}
     */
    public function postUpdateReturnStatus()
    {

        $this->hopesLog->log(self::LOG_TYPE, '[Start] UpdateReturnStatus Start');
        

        $body = $this->request->getBodyParams();

        $this->hopesLog->log(self::LOG_TYPE, '[Request Body]' . json_encode($body, JSON_UNESCAPED_UNICODE));

        foreach ($body as $data) {
            $this->hopesLog->log(self::LOG_TYPE, '[PARTNO] ' . $data['PARTNO']);
            

            try {
                $record = $this->findRmaRecords($data);

                if (!$record) {
                    $this->result[$data['B2CHDORDNO']][$data['PARTNO']]
                    = Message::getReturnMessageList(Message::RETURN_ERROR_FAILED_UPDATE_RMA);
                    
                    $this->hopesLog->log(self::LOG_TYPE, 'Cannot find rma record.');
                    continue;
                }

                 //退貨申請不同意
                if ($data['RTNQCSTAT'] != 'Y') {
                    $note = [
                        'status' => RmaStatus::RETURN_APPLY_DECLINE,
                        'hopes_return_note' => json_encode($data, JSON_UNESCAPED_UNICODE),
                    ];

                    $this->updateStatus($record, $note);

                    $this->hopesLog->log(self::LOG_TYPE, 'Apply return decline.');
                    continue;
                }

                $taxId = $record->getCustomerTaxIdNumber();
                $statusDefault = is_null($taxId) ? RmaStatus::RETURN_REFUND_PROCESSING : RmaStatus::RETURN_FINANCIAL_REVIEW_PROCESSING;

                $note = [
                    'status' => $statusDefault,
                    'hopes_return_note' => json_encode($data, JSON_UNESCAPED_UNICODE),
                ];

                
                $note = $this->createCreditmemo($record, $note);

                //update status
                $this->updateStatus($record, $note);

                $this->result[$data['B2CHDORDNO']][$data['PARTNO']] = Message::getReturnMessageList(Message::RETURN_SUCCESS_UPDATE);
            } catch (\Exception $e) {
                $this->hopesLog->logException(self::LOG_TYPE, $e, ['data' => $data]);
                $this->result[$data['B2CHDORDNO']][$data['PARTNO']] = Message::getReturnMessageList(Message::RETURN_ERROR_FAILED_UPDATE_RMA);
            }
        }

        $this->hopesLog->log(self::LOG_TYPE, '[Response]' . json_encode($this->result, JSON_UNESCAPED_UNICODE));

        $this->setResponse($this->result);

    }

    /**
     * findRmaRecords
     *
     * @param  array $data
     * @return mixed
     */
    public function findRmaRecords($data)
    {
        $orders = $this->orderCollectionFactory->create()
                    ->addFieldToFilter('hotai_child_order_number', $data['B2CHDORDNO']);
        
        foreach($orders->getData() as $singleOrder) {
            // get the lastest rma record
            $rmaCollection = $this->details->create()
                ->addFieldToFilter('order_id', $singleOrder['entity_id'])
                ->addFieldToFilter('status', ['nin' => \Branch8\Rma\Model\Rma\Status::declinedStatus()]);
    
            foreach ($rmaCollection->getItems() as $rmaRecord) {
                $product = $this->productRepository->getById($rmaRecord->getProductId());
    
                if (is_null($product->getData('hotai1_PARTNO'))) {
                    continue;
                }
    
                if ($product->getData('hotai1_PARTNO') == $data['PARTNO']) {
                    return $rmaRecord;
                }
            }
        }

        return false;

    }

    private function updateStatus($record, $note){
        $record->addData($note)->save();

        $items = $this->rmaHelper->getRmaItemsByRmaId($record->getId());

        $status = $this->statusLabel->getAdminAndSellerPanelRmaStatusTitle($note['status']);
        $this->status->createStatusRecord($status, $record);

        foreach($items as $singleItem) {
            $this->itemStatus->updateItemStatusById(
                $singleItem->getItemId(), $status, $record->getOrderId(), true, $record->getId()
            );
        }
        

        $productDetails = $this->rmaHelper->getRmaProductDetails($record->getId());
        foreach ($productDetails as $item) {
            $item->setFlowStatus($status);
            $item->save();
        }
    }


    private function createCreditmemo($record, $note){
        $invoice = $this->getLastInvoiceId($record);

        if (!$invoice) {
            throw new Exception('There is no invoice, cannot issue creditmemo.');
        }

        $productDetails = $this->rmaHelper->getRmaProductDetails($record->getId());
        $totalPrice = 0;
        $totalPoint = 0;

        if ($productDetails->getSize()) {
            foreach ($productDetails as $singleItem) {
                $totalPrice += $this->rmaHelper->getItemFinalPrice($singleItem);
                $totalPoint = $totalPoint + (int) $singleItem->getRowTotalPointUsed();
            }
        }

        $negative = $totalPoint;
        $shippingAmount = $this->getSellerShippingAmount($totalPrice, $record->getOrderId());
        
        $data = [
            'rma_id' => $record->getId(),
            'negative' => $negative,
            'do_offline' => '1',
            'invoice_id' => $invoice,
            'back_to_stock' => true,
            'seller_shipping_amount' => $shippingAmount,
        ];

        $result = $this->rmaHelper->createCreditMemo($data);

        if ($result['error']) {
            throw new Exception($result['msg']);
        } else {
            $note['memo_id'] = $result['memo_id'];
            $note['refunded_amount'] = $totalPrice + $shippingAmount;
            $note['refunded_point'] = $totalPoint;

            $this->rmaHelper->updateItemRefundColumnAfterIssueCreditMemo($result['memo_id']);
            $this->rmaHelper->updateMpOrder($record->getOrderId(), $result['memo_id']);
        }

        return $note;
    }


    private function getLastInvoiceId($record){
        $invoiceId = $this->rmaHelper
            ->getMpOrder($record->getOrderId(), $record->getSellerId())->getInvoiceId();

        if ($invoiceId) {
            $myinvoiceId = explode(',', $invoiceId);
            foreach ($myinvoiceId as $invoiceId) {
                if ($invoiceId) {
                    $invoiceId = $invoiceId;
                }
            }
        }

        return $invoiceId;
    }
    
    /**
     * getSellerShippingAmount
     *  
     *  退款如果是整單，就退運費。
     *  如果未退整單就不退運費。
     *  假設這商品只有 1 個還有運費，那就全部都退
     *
     * @return int
     */
    private function getSellerShippingAmount($totalPrice, $orderid){
        $order = $this->rmaHelper->getOrder($orderid);
        $refundableAmount = $order->getTotalPaid() - $order->getTotalRefunded();

        // 可退款金額大於現在要退款的金額
        if ($refundableAmount > ((int) $totalPrice + (int) $order->getShippingInclTax())) {
            return 0;
        }

        return (int) $order->getShippingInclTax();
        
    }
}
