<?php

namespace Branch8\Refund\Helper;

use Branch8\Sales\Model\CreditMemo\FinancialReviewStatus;
use \Magento\Sales\Api\CreditmemoRepositoryInterface;
use \Magento\Sales\Model\Order\Creditmemo;
use \Magento\Sales\Api\CreditmemoManagementInterface;
use \Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Model\Order\Creditmemo\Item;
use \Magento\Sales\Model\Order\Creditmemo\ItemCreationFactory as CreditMemoItems;
use Branch8\Sales\Model\CreditMemo\VoidInvoiceType;
use \Branch8\Sales\Model\CreditMemo\CreditMemoStatus;
use Exception;

class CreditmemoRefundData
{
    private const LOG_CLASS_KEY = 'CreditmemoRefundData';

    /** @var \Magento\Sales\Model\Order\Creditmemo\ItemCreationFactory $creditMemoItems */
    protected $creditMemoItems;

    /** @var \Magento\Sales\Api\CreditmemoManagementInterface $creditmemoManagement */
    protected $creditmemoManagement;

    /** @var \Magento\Sales\Model\Order\CreditmemoFactory $creditMemoFactory */
    protected $creditMemoFactory;

    /** @var \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository */
    protected $creditmemoRepository;

    protected $eventManager;

    /** @var ConfigurableRefundLogger */
    private $refundLogger;

    /**
     * @param CreditMemoItems $creditMemoItems Item creation factory
     * @param CreditmemoManagementInterface $creditmemoManagement Memo refund API
     * @param Creditmemo $creditMemoFactory Credit memo model (load by id)
     * @param CreditmemoRepositoryInterface $creditmemoRepository Memo repository
     * @param CreditmemoInterface $creditmemoInterface Unused legacy DI parameter
     * @param \Magento\Framework\Event\ManagerInterface $eventManager Event dispatcher
     * @param ConfigurableRefundLogger $refundLogger Admin-gated file logger
     */
    public function __construct(
        CreditMemoItems $creditMemoItems,
        CreditmemoManagementInterface $creditmemoManagement,
        Creditmemo $creditMemoFactory,
        CreditmemoRepositoryInterface $creditmemoRepository,
        CreditmemoInterface $creditmemoInterface,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        ConfigurableRefundLogger $refundLogger
    ) {
        $this->creditMemoItems = $creditMemoItems;
        $this->creditmemoManagement = $creditmemoManagement;
        $this->creditMemoFactory = $creditMemoFactory;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->eventManager = $eventManager;
        $this->refundLogger = $refundLogger;
    }

    /**
     * Whether ECPay invoice sync flagged success on the credit memo.
     *
     * @param int|string $memoId Credit memo entity id
     * @return bool
     */
    public function IsInvoiceProcessDone($memoId)
    {

        $creditMemo = $this->creditMemoFactory->load($memoId);
        //$creditMemo = $this->creditmemoRepository->get($memoId);

        if(!$creditMemo) {
            return false;
        }

        if ($creditMemo->getIsInvoiceSuccess() == 1) {
            return true;
        }

        return false;

    }

    /**
     * Persist refund outcome on memo, order items, and optionally dispatch RMA event.
     *
     * @param int|string $memoId Credit memo id
     * @param array<string, mixed> $data Must include creditmemo_status, refunded_amount semantics per caller
     * @return void
     */
    public function setRefundedData($memoId, $data) {
        $creditMemo = $this->creditmemoRepository->get($memoId);
        $creditMemo->setCreditmemoStatus($data['creditmemo_status']);

        $itemFlowStatus = \Branch8\HotaiCore\Model\Order\Status::STATUS_RETURNED;

        if ($data['creditmemo_status'] == CreditMemoStatus::REFUND_FAIL) {
            $itemFlowStatus = \Branch8\HotaiCore\Model\Order\Status::STATUS_RETURN_REFUND_FAIL;
        }

        // Updated Items
        foreach ($creditMemo->getAllItems() as $creditmemoItem) {
            $orderItem = $creditmemoItem->getOrderItem();
            $orderItem->setFlowStatus($itemFlowStatus);
            $orderItem->save();

            if ($data['creditmemo_status'] == CreditMemoStatus::REFUND_FAIL) {
                continue;
            }

            //update item returned qty
            $orderItem->setQtyReturned((int) $orderItem->getQtyReturned() + (int) $creditmemoItem->getQty());
            $orderItem->save();
        }

        if ($data['creditmemo_status'] == CreditMemoStatus::REFUND_SUCCESS) {
            $creditMemo->setRefundedAmount($creditMemo->getGrandTotal());
        }   
        
        $creditMemo->setData('stop_validation', true);
        $this->creditmemoRepository->save($creditMemo);
        if ($data['creditmemo_status'] == CreditMemoStatus::REFUND_SUCCESS) {
            $this->eventManager->dispatch(
                'rma_return_approved',
                ['creditmemo' => $creditMemo]
            );
        }

    }

    /**
     * Load memo line items or empty array on failure.
     *
     * @param int|string $memoId Credit memo id
     * @return Item[]
     */
    public function getCreditMemoItems($memoId) {
        try {
            $creditMemo = $this->creditmemoRepository->get($memoId);
            return $creditMemo->getAllItems();
        } catch (Exception $e) {
            $this->refundLogger->logException(self::LOG_CLASS_KEY, $e, 'getCreditMemoItems');

            return [];
        }

    }
}
