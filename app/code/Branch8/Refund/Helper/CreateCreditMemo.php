<?php

namespace Branch8\Refund\Helper;

use \Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader;
use \Magento\Sales\Api\CreditmemoManagementInterface;
use \Magento\Framework\Registry;
use Magento\Sales\Model\Order\Creditmemo\ItemCreationFactory;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Model\Order\Creditmemo\RefundOperation;
use \Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;

class CreateCreditMemo
{
    public const ECPAY_INVOICE_CANCEL = 3;

    private const LOG_CLASS_KEY = 'CreateCreditMemo';

    protected $orderRepository;

    protected $refundOrder;

    protected $itemRepository;

     /**
     * @var \Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader
     */
    protected $memoLoader;

    /**
     * @var \Magento\Sales\Api\CreditmemoManagementInterface
     */
    protected $creditmemoManagement;
    /**
     * @var Registry
     */
    protected $registry;

    protected $itemCreationFactory;

    protected $creditMemoFactory;

    protected $creditmemoRepository;

    protected $creditmemoService;

    protected $refundOperation;

    protected $order;

    /** @var \Magento\Framework\Pricing\PriceCurrencyInterface*/
    private $priceCurrency;

    /** @var ConfigurableRefundLogger */
    private $refundLogger;

    /**
     * @param ItemCreationFactory $itemCreationFactory Credit memo item creation factory
     * @param CreditmemoLoader $creditmemoLoader Admin credit memo loader
     * @param CreditmemoManagementInterface $creditmemoManagement Refund API
     * @param Registry $registry Registry for current memo
     * @param CreditmemoFactory $creditMemoFactory Memo factory
     * @param CreditmemoRepositoryInterface $creditmemoRepository Memo repository
     * @param \Magento\Sales\Model\Service\CreditmemoService $creditmemoService Memo service
     * @param RefundOperation $refundOperation Core refund operation (namesake conflict with Magento class)
     * @param OrderRepositoryInterface $order Order repository
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency Currency rounding
     * @param ConfigurableRefundLogger $refundLogger Admin-gated file logger
     */
    public function __construct(
        ItemCreationFactory $itemCreationFactory,
        CreditmemoLoader $creditmemoLoader,
        CreditmemoManagementInterface $creditmemoManagement,
        Registry $registry,
        CreditmemoFactory $creditMemoFactory,
        CreditmemoRepositoryInterface $creditmemoRepository,
        \Magento\Sales\Model\Service\CreditmemoService $creditmemoService,
        RefundOperation $refundOperation,
        OrderRepositoryInterface $order,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        ConfigurableRefundLogger $refundLogger
    ) {
        $this->itemCreationFactory = $itemCreationFactory;
        $this->memoLoader = $creditmemoLoader;
        $this->creditmemoManagement    = $creditmemoManagement;
        $this->registry = $registry;
        $this->creditMemoFactory = $creditMemoFactory;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->creditmemoService = $creditmemoService;
        $this->refundOperation = $refundOperation;
        $this->order = $order;
        $this->priceCurrency = $priceCurrency;
        $this->refundLogger = $refundLogger;
    }

    /**
     * Build and refund a credit memo for the whole invoiced qty (admin loader flow).
     *
     * @param \Magento\Sales\Model\Order $order Order entity
     * @param int $type Issue type (cancel/return)
     * @param bool $returnStock Whether items go back to stock
     * @return array{msg: \Magento\Framework\Phrase|string, error: int|string, memo_id?: int, memo?: \Magento\Sales\Api\Data\CreditmemoInterface}
     */
    public function execute($order, $type = \Branch8\Sales\Model\CreditMemo\IssueType::RETURN, $returnStock = false)
    {

        $result = ['msg' => '', 'error' => ''];

        //Set Force Credit Memo Value
        $order->setForcedCanCreditmemo("true");
        $order->save();

        if (!$order->canCreditmemo()) {
            $result['error'] = 1;
            $result['msg'] = 'Cannot create credit memo for order : ' . $order->getIncrementId();
            return $result;
        }

        $itemIdsToRefund = [];
        $pointDiscount = 0;

        foreach ($order->getAllItems() as $orderItem) {

            if ($orderItem->getRmaStatus() == \Branch8\Rma\Model\Rma\Status::RMA_PROCESSING) {
                continue;
            }
            
            $itemIdsToRefund[$orderItem->getItemId()]
                = ['qty' => $orderItem->getQtyInvoiced()];
            $pointDiscount = $pointDiscount + (int)$orderItem->getRowTotalPointUsed();
          }

        $memoData = [
            'items' => $itemIdsToRefund,
            'do_offline' => "1",
            'comment_text' => "",
            'back_to_stock'=> $returnStock,
            'adjustment_positive' => '',
            'adjustment_negative' => $pointDiscount
        ];

        try {

            if ($this->registry->registry('current_creditmemo')) {
                $this->registry->unregister('current_creditmemo');
            }

            $this->memoLoader->setOrderId($order->getId());
            $this->memoLoader->setCreditmemoId("");
            $this->memoLoader->setCreditmemo($memoData);
            $this->memoLoader->setInvoiceId($this->getFirstInvoiceId($order));
            $memo = $this->memoLoader->load();

            if ($memo) {
                if (!$memo->isValidGrandTotal()) {
                    $result['msg'] = __('Total must be positive.');
                    $result['error'] = 1;
                    return $result;
                }

                if (!empty($memo['comment_text'])) {
                    $memo->addComment(
                        $memo['comment_text'],
                        isset($memo['comment_customer_notify']),
                        isset($memo['is_visible_on_front'])
                    );

                    $memo->setCustomerNote($memo['comment_text']);
                    $memo->setCustomerNoteNotify(isset($memo['comment_customer_notify']));
                }

                $memo->setIssueType($type);
                if ($returnStock) {
                    foreach ($memo->getItems() as $item) {
                        $item->setBackToStock(true);
                    }
                }

                $grandTotal = $memo->getGrandTotal();
                
                if ($grandTotal >= 0) {
                    $this->creditmemoManagement->refund($memo, true);
                }

                $result['msg'] = __('Credit memo generated successfully.');
                $result['error'] = 0;
                $result['memo_id'] = $memo->getId();
                $result['memo'] = $memo;

                return $result;
            }

            $result['msg'] = __('There is no memo created.');
            $result['error'] = 1;
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->refundLogger->logException(self::LOG_CLASS_KEY, $e, 'execute');
            $result['msg'] = $e->getMessage();
            $result['error'] = 1;
        } catch (\Exception $e) {
            $this->refundLogger->logException(self::LOG_CLASS_KEY, $e, 'execute');
            $result['msg'] = __('Unable to save credit memo right now.');
            $result['error'] = 1;
        }

        return $result;

    }

    /**
     * getLatestInvoiceId
     *
     * @param  mixed $order
     * @return int|string|null
     */
    private function getLatestInvoiceId($order) {
        return $order->getInvoiceCollection()->getLastItem()->getId();
    }

    /**
     * getFirstInvoiceId
     *
     * @param  mixed $order
     * @return int|string|null
     */
    private function getFirstInvoiceId($order) {
        return $order->getInvoiceCollection()->getFirstItem()->getId();
    }

    public function updateOrderByCreditMemo(CreditmemoInterface $creditmemo, OrderInterface $order, $online = false) {
        $baseOrderRefund = $this->priceCurrency->round(
            $order->getBaseTotalRefunded() + $creditmemo->getBaseGrandTotal()
        );
        $orderRefund = $this->priceCurrency->round(
            $order->getTotalRefunded() + $creditmemo->getGrandTotal()
        );
        $order->setBaseTotalRefunded($baseOrderRefund);
        $order->setTotalRefunded($orderRefund);

        $order->setBaseSubtotalRefunded($order->getBaseSubtotalRefunded() + $creditmemo->getBaseSubtotal());
        $order->setSubtotalRefunded($order->getSubtotalRefunded() + $creditmemo->getSubtotal());

        $order->setBaseTaxRefunded($order->getBaseTaxRefunded() + $creditmemo->getBaseTaxAmount());
        $order->setTaxRefunded($order->getTaxRefunded() + $creditmemo->getTaxAmount());
        $order->setBaseDiscountTaxCompensationRefunded(
            $order->getBaseDiscountTaxCompensationRefunded() + $creditmemo->getBaseDiscountTaxCompensationAmount()
        );
        $order->setDiscountTaxCompensationRefunded(
            $order->getDiscountTaxCompensationRefunded() + $creditmemo->getDiscountTaxCompensationAmount()
        );

        $order->setBaseShippingRefunded($order->getBaseShippingRefunded() + $creditmemo->getBaseShippingAmount());
        $order->setShippingRefunded($order->getShippingRefunded() + $creditmemo->getShippingAmount());

        $order->setBaseShippingTaxRefunded(
            $order->getBaseShippingTaxRefunded() + $creditmemo->getBaseShippingTaxAmount()
        );
        $order->setShippingTaxRefunded($order->getShippingTaxRefunded() + $creditmemo->getShippingTaxAmount());

        $order->setAdjustmentPositive($order->getAdjustmentPositive() + $creditmemo->getAdjustmentPositive());
        $order->setBaseAdjustmentPositive(
            $order->getBaseAdjustmentPositive() + $creditmemo->getBaseAdjustmentPositive()
        );

        $order->setAdjustmentNegative($order->getAdjustmentNegative() + $creditmemo->getAdjustmentNegative());
        $order->setBaseAdjustmentNegative(
            $order->getBaseAdjustmentNegative() + $creditmemo->getBaseAdjustmentNegative()
        );

        $order->setDiscountRefunded($order->getDiscountRefunded() + $creditmemo->getDiscountAmount());
        $order->setBaseDiscountRefunded($order->getBaseDiscountRefunded() + $creditmemo->getBaseDiscountAmount());

        if ($online) {
            $order->setTotalOnlineRefunded($order->getTotalOnlineRefunded() + $creditmemo->getGrandTotal());
            $order->setBaseTotalOnlineRefunded(
                $order->getBaseTotalOnlineRefunded() + $creditmemo->getBaseGrandTotal()
            );
        } else {
            $order->setTotalOfflineRefunded($order->getTotalOfflineRefunded() + $creditmemo->getGrandTotal());
            $order->setBaseTotalOfflineRefunded(
                $order->getBaseTotalOfflineRefunded() + $creditmemo->getBaseGrandTotal()
            );
        }

        $order->setBaseTotalInvoicedCost(
            $order->getBaseTotalInvoicedCost() - $creditmemo->getBaseCost()
        );

        return $order;
    }

    public function getCreditmemo($memoId) {
        return $this->creditmemoRepository->get($memoId);
    }

    public function deductionOrderByCreditMemo(CreditmemoInterface $creditmemo, OrderInterface $order, $online = false) {
        $baseOrderRefund = $this->priceCurrency->round(
            $order->getBaseTotalRefunded() - $creditmemo->getBaseGrandTotal()
        );
        $orderRefund = $this->priceCurrency->round(
            $order->getTotalRefunded() - $creditmemo->getGrandTotal()
        );
        $order->setBaseTotalRefunded($baseOrderRefund);
        $order->setTotalRefunded($orderRefund);

        $order->setBaseSubtotalRefunded($order->getBaseSubtotalRefunded() - $creditmemo->getBaseSubtotal());
        $order->setSubtotalRefunded($order->getSubtotalRefunded() - $creditmemo->getSubtotal());

        $order->setBaseTaxRefunded($order->getBaseTaxRefunded() - $creditmemo->getBaseTaxAmount());
        $order->setTaxRefunded($order->getTaxRefunded() - $creditmemo->getTaxAmount());
        $order->setBaseDiscountTaxCompensationRefunded(
            $order->getBaseDiscountTaxCompensationRefunded() - $creditmemo->getBaseDiscountTaxCompensationAmount()
        );
        $order->setDiscountTaxCompensationRefunded(
            $order->getDiscountTaxCompensationRefunded() - $creditmemo->getDiscountTaxCompensationAmount()
        );

        $order->setBaseShippingRefunded($order->getBaseShippingRefunded() - $creditmemo->getBaseShippingAmount());
        $order->setShippingRefunded($order->getShippingRefunded() - $creditmemo->getShippingAmount());

        $order->setBaseShippingTaxRefunded(
            $order->getBaseShippingTaxRefunded() - $creditmemo->getBaseShippingTaxAmount()
        );
        $order->setShippingTaxRefunded($order->getShippingTaxRefunded() - $creditmemo->getShippingTaxAmount());

        $order->setAdjustmentPositive($order->getAdjustmentPositive() - $creditmemo->getAdjustmentPositive());
        $order->setBaseAdjustmentPositive(
            $order->getBaseAdjustmentPositive() - $creditmemo->getBaseAdjustmentPositive()
        );

        $order->setAdjustmentNegative($order->getAdjustmentNegative() - $creditmemo->getAdjustmentNegative());
        $order->setBaseAdjustmentNegative(
            $order->getBaseAdjustmentNegative() - $creditmemo->getBaseAdjustmentNegative()
        );

        $order->setDiscountRefunded($order->getDiscountRefunded() - $creditmemo->getDiscountAmount());
        $order->setBaseDiscountRefunded($order->getBaseDiscountRefunded() - $creditmemo->getBaseDiscountAmount());

        if ($online) {
            $order->setTotalOnlineRefunded($order->getTotalOnlineRefunded() - $creditmemo->getGrandTotal());
            $order->setBaseTotalOnlineRefunded(
                $order->getBaseTotalOnlineRefunded() - $creditmemo->getBaseGrandTotal()
            );
        } else {
            $order->setTotalOfflineRefunded($order->getTotalOfflineRefunded() - $creditmemo->getGrandTotal());
            $order->setBaseTotalOfflineRefunded(
                $order->getBaseTotalOfflineRefunded() - $creditmemo->getBaseGrandTotal()
            );
        }

        $order->setBaseTotalInvoicedCost(
            $order->getBaseTotalInvoicedCost() + $creditmemo->getBaseCost()
        );

        return $order;
    }

    public function afterCancellationApprove($order, $rmaDetail){

        $order->setEcpayInvoiceAutoTag(self::ECPAY_INVOICE_CANCEL);
        $order->save();

        $result = $this->execute(
            $order,
            \Branch8\Sales\Model\CreditMemo\IssueType::CANCEL,
            true
        );

        if ($result['error'] == 1) {
            throw new \Magento\Framework\Exception\LocalizedException(__($result['msg']));
        }

        $order = $this->updateOrderByCreditMemo($result['memo'], $order, false);
        $order->save();
        //$this->orderRepository->save($order);

        return  $result['memo_id'];
    }
}
