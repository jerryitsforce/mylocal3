<?php

namespace Ecpay\General\Cron;

use Carbon\Carbon;
use DateTime;
use Ecpay\General\Helper\Foundation\GeneralHelper;
use Ecpay\Sdk\Exceptions\RtnException;
use Ecpay\General\Helper\Services\Config\InvoiceService;
use JsonException;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Zend_Log_Exception;

class OrderAutoInvalidInvoice
{
    public const INVOICE_COUNT = 1;

    protected GeneralHelper $_loggerInterface;
    protected InvoiceService $_invoiceService;
    protected ResourceConnection $resourceConnection;
    protected TimezoneInterface $_timeZone;

    /**
     * @param GeneralHelper $loggerInterface
     * @param InvoiceService $invoiceService
     * @param ResourceConnection $resourceConnection
     * @param TimezoneInterface $timeZone
     */
    public function __construct(
        GeneralHelper $loggerInterface,
        InvoiceService $invoiceService,
        ResourceConnection $resourceConnection,
        TimezoneInterface $timeZone,
    ) {
        $this->_loggerInterface   = $loggerInterface;
        $this->_invoiceService    = $invoiceService;
        $this->resourceConnection = $resourceConnection;
        $this->_timeZone          = $timeZone;
    }

    /**
     * @return $this
     * @throws FileSystemException
     * @throws JsonException
     * @throws RtnException
     * @throws Zend_Log_Exception
     * @throws \Exception
     */
    public function execute()
    {
        /**
         * @var  $parentOrders \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder\Collection
         * @var  $parentOrder \Branch8\MarketPlaceParentOrder\Model\ParentOrder
         */
        $this->writeLog(
            '-----------------------------ECPay Cron Job OrderAutoInvalidInvoice----------------------------------'
        );

        $orderIds = $this->getCreditMemoOnlyOrderId();

        foreach ($orderIds as $orderId) {
            $order_id = $orderId['order_id'];
            $financial_review_status = $orderId['financial_review_status'];
            $column = ((int)$financial_review_status === 0) ? 'created_at' : 'finanical_approve_at';
            $creditMemo = $this->getCreditMemoByOrderId($order_id, $column);
            if (count($creditMemo) === 0) {
                continue;
            }

            $status             = false;
            $entityId           = $creditMemo['entity_id'];
            $orderId            = $creditMemo['order_id'];

            $hifiLogCount = count($this->getEcpayInvoiceHotaiOrderInvoiceLogs($orderId));

            if ($hifiLogCount === 0) {
                continue;
            }

            $isVoidedInvoice    = $creditMemo['is_voided_invoice'];
            $isIssuedOffInvoice = $creditMemo['is_issued_offinvoice'];
            $shippingInclTax    = (int)$creditMemo['shipping_incl_tax'];
            $creditMemos = $this->getCreditMemos($orderId, $entityId);
            $baseAdjustmentFees = $this->columnToArray('base_adjustment', $creditMemos);
            $discountAmounts = $this->columnToArray('discount_amount', $creditMemos);
            $grandTotals = $this->columnToArray('grand_total', $creditMemos);
            $baseAdjustment = (int)array_sum($baseAdjustmentFees);
            $discountAmount = (int)array_sum($discountAmounts);
            $grandTotal = (int)array_sum($grandTotals);
            $invoiceCount = count($this->getEcpayInvoiceHotaiOrderLogsWithOrderIdAndReverse($orderId));

            $creditMemosForInvalid = $this->getCreditMemos($orderId, $entityId, $entityId);
            $invalidGrandTotals = $this->columnToArray('grand_total', $creditMemosForInvalid);
            $invalidGrandTotal = (int)array_sum($invalidGrandTotals);

            $orderItemIds = $this->getCreditMemoItemWithQty($orderId, $column, $entityId);
            $invalidOrderItemIds = $this->getCreditMemoItemWithQty($orderId, $column);

            /**
             * 手動判斷跨月時間
             */
            $voidInvoiceType = (int)$creditMemo['void_invoice_type'];
            $order = $this->getOrder($orderId);
            $today       = $this->_timeZone->convertConfigTimeToUtc($this->_timeZone->date());
            $monthFormat    = $this->_timeZone->date($today)->format('Y-m');

            if (!empty($order['ecpay_invoice_updated_at'])) {
                $date = new DateTime($order['ecpay_invoice_updated_at']);
                $invoiceDate = $date->format('Y-m');

                if ($voidInvoiceType === 1 || $voidInvoiceType === 2) {
                    if ($monthFormat !== $invoiceDate) {
                        $voidInvoiceType = 2;
                    } else {
                        $voidInvoiceType = 1;
                    }
                }

                if ($voidInvoiceType === 3 || $voidInvoiceType === 4) {
                    if ($monthFormat !== $invoiceDate) {
                        $voidInvoiceType = 4;
                    } else {
                        $voidInvoiceType = 3;
                    }
                }
            }

            switch ($voidInvoiceType) {
                //1:作廢
                case 1:
                    $this->_loggerInterface->writeLog('作廢發票', 'cron_orderAutoInvalidInvoice');
                    if ($this->invalidInvoice(
                        $orderId,
                        $entityId,
                        $isVoidedInvoice,
                        $creditMemo['void_invoice_retry_count'],
                        $invalidOrderItemIds,
                        $invoiceCount,
                        $invalidGrandTotal
                    )) {
                        $status = true;
                    }
                    break;
                //2:折讓
                case 2:
                    $this->_loggerInterface->writeLog('折讓發票', 'cron_orderAutoInvalidInvoice');
                    if ($this->allowanceInvoice(
                        $orderId,
                        $entityId,
                        $isIssuedOffInvoice,
                        $creditMemo['issue_offinvoice_retry_count'],
                        $invalidOrderItemIds,
                        $invoiceCount,
                        $invalidGrandTotal
                    )) {
                        $status = true;
                    }
                    break;
                //3:作廢且重開
                case 3:
                    $this->_loggerInterface->writeLog('作廢且重開發票', 'cron_orderAutoInvalidInvoice');
                    if ($this->invalidInvoice(
                            $orderId,
                            $entityId,
                            $isVoidedInvoice,
                            $creditMemo['void_invoice_retry_count'],
                            $invalidOrderItemIds,
                            $invoiceCount,
                            $invalidGrandTotal
                        ) && $this->reInvoice(
                            $entityId,
                            $orderId,
                            $grandTotal,
                            $creditMemo['reissue_invoice_retry_count'],
                            $shippingInclTax,
                            $baseAdjustment,
                            $discountAmount,
                            $invoiceCount,
                            $orderItemIds
                        )) {
                        $status = true;
                    }
                    break;
                //4.作廢且折讓
                case 4:
                    $this->_loggerInterface->writeLog('作廢且折讓發票', 'cron_orderAutoInvalidInvoice');
                    if ($this->allowanceInvoice(
                            $orderId,
                            $entityId,
                            $isIssuedOffInvoice,
                            $creditMemo['issue_offinvoice_retry_count'],
                            $invalidOrderItemIds,
                            $invoiceCount,
                            $invalidGrandTotal
                        ) && $this->reInvoice(
                            $entityId,
                            $orderId,
                            $grandTotal,
                            $creditMemo['reissue_invoice_retry_count'],
                            $shippingInclTax,
                            $baseAdjustment,
                            $discountAmount,
                            $invoiceCount,
                            $orderItemIds
                        )) {
                        $status = true;
                    }
                    break;
            }

            if ($status) {
                $this->updateCreditMemo($entityId, [
                    'is_invoice_success' => 1,
                    'invoice_success_at' => Carbon::now('Asia/Taipei')
                ]);
            }
        }

        return $this;
    }

    /**
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     */
    private function writeLog($message): void
    {
        $fileName = 'cron_orderAutoInvalidInvoice' . trim(date("Y_m_d"), '/');
        $this->_loggerInterface->writeLog($message, 'cron', $fileName);
    }

    /**
     * @param $orderId
     * @return array
     */
    protected function getEcpayInvoiceHotaiOrderLogsWithOrderIdAndReverse($orderId): array
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()->from('ecpay_invoice_hotai_order_invoice_logs');

        $select->where('status IN (?)', [1, 4])
            ->where('is_reverse = ?', 0)
            ->where('order_id = ?', $orderId);

        return $connection->fetchAll($select);
    }

    /**
     * @param $orderId
     * @param $column
     * @param $entityId
     * @return array
     */
    protected function getCreditMemoItemWithQty($orderId, $column, $entityId = ''): array
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from(['cmi' => 'sales_creditmemo_item'], ['order_item_id', 'qty'])
            ->join(
                ['cm' => 'sales_creditmemo'],
                'cmi.parent_id = cm.entity_id',
                [] // 不需要從 sales_creditmemo 中選取其他欄位
            )
            ->where('cm.'.$column.' IS NOT NULL')
            ->where('cm.is_invoice_success = 1')
            ->where('cm.order_id = ?', $orderId)
            ;

        if ($entityId !== '') {
            $select = $select->orWhere('cm.entity_id = ?', $entityId);
        }

        $result = $connection->fetchAll($select);

        // 重新格式化結果，以 order_item_id 作為鍵
        $formattedResult = [];
        foreach ($result as $item) {
            $formattedResult[$item['order_item_id']] = (int)$item['qty'];
        }

        return $formattedResult;
    }


    /**
     * @param $orderId
     * @param $column
     * @return array
     */
    protected function getCreditMemoByOrderId($orderId, $column): array
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from('sales_creditmemo')
            ->where("$column IS NOT NULL")
            ->where('is_invoice_success = ?', 0)
            ->where('financial_review_status IN (?)', [0, 2])
            ->where('void_invoice_type IN (?)', [1, 2, 3, 4])
            ->where('order_id = ?', $orderId)
            ->order("$column ASC")
            ->limit(1);

        $result = $connection->fetchRow($select);
        if ($result === false) {
            return [];
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function getCreditMemoOnlyOrderId(): array
    {
        $connection = $this->resourceConnection->getConnection();

        $sevenDaysAgo = (new DateTime())->modify('-14 days')->format('Y-m-d H:i:s');

        $select = $connection->select()
            ->from('sales_creditmemo', ['order_id', 'financial_review_status'])
            ->where('is_invoice_success = 0')
            ->where('financial_review_status IN (0, 2)')
            ->where('void_invoice_type IN (1, 2, 3, 4)')
            ->where('finanical_approve_at >= ?', $sevenDaysAgo)
            ->orWhere('updated_at >= ?', $sevenDaysAgo)
            ->group('order_id');

        return $connection->fetchAll($select);
    }

    /**
     * @param $orderId
     * @param $entityId
     * @param string $exceptEntityId
     * @return array
     */
    protected function getCreditMemos($orderId, $entityId, $exceptEntityId = ''): array
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from('sales_creditmemo');

        // 拿取過往包含自己的creditmemo
        $select->where('financial_review_status IN (?)', [0, 2])
            ->where('void_invoice_type IN (?)', [1, 2, 3, 4])
            ->where('order_id = ?', $orderId)
            ->where('entity_id <= ?', $entityId);

        if ($exceptEntityId !== '') {
            $select->where('entity_id != ?', $exceptEntityId);
        }
        return $connection->fetchAll($select);
    }

    /**
     * @param int $memoId
     * @param array $memoItemData
     * @return void
     */
    protected function updateCreditMemo(int $memoId, array $memoItemData): void
    {
        $connection = $this->resourceConnection->getConnection();

        $table = $connection->getTableName('sales_creditmemo');

        $connection->update(
            $table,
            $memoItemData,
            ['entity_id = ?' => $memoId]
        );
    }

    /**
     * @throws RtnException
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     */
    private function invalidInvoice($orderId, $memoId, $isVoidedInvoice, $count, $orderItemIds, $invoiceCount, $grandTotals): bool
    {
        if ((int)$isVoidedInvoice === 1) {
            return true;
        }

        /** 不管怎麼樣都先作廢發票 */
        $invalidInvoice = $this->_invoiceService->invalidInvoiceForRma($orderId, $orderItemIds, $invoiceCount, 'invalid', $grandTotals);
        if ($invalidInvoice['code'] !== '0999') {
            /** 1005代表找不到發票 */
            if ($invalidInvoice['code'] === '1005') {
                $this->updateCreditMemo($memoId, [
                    'void_invoice_retry_count' => ++$count
                ]);
            }
            /** 作廢不成功就先跑下一筆 */
            return false;
        }

        $this->updateCreditMemo($memoId, [
            'is_voided_invoice'        => 1,
            'void_invoice_retry_count' => ++$count
        ]);

        return true;
    }

    /**
     * @throws RtnException
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     */
    private function allowanceInvoice($orderId, $memoId, $isIssuedOffInvoice, $count, $orderItemIds, $invoiceCount, $grandTotals): bool
    {
        if ((int)$isIssuedOffInvoice === 1) {
            return true;
        }

        /** 不管怎麼樣都先作廢發票 */
        $allowanceInvoice = $this->_invoiceService->invalidInvoiceForRma($orderId, $orderItemIds, $invoiceCount, 'allowance', $grandTotals);
        if ($allowanceInvoice['code'] !== '0999') {
            /** 1005代表找不到發票 */
            if ($allowanceInvoice['code'] === '1005') {
                $this->updateCreditMemo($memoId, [
                    'issue_offinvoice_retry_count' => ++$count
                ]);
            }
            /** 作廢不成功就先跑下一筆 */
            return false;
        }

        $this->updateCreditMemo($memoId, [
            'is_issued_offinvoice'         => 1,
            'issue_offinvoice_retry_count' => ++$count
        ]);

        return true;
    }

    /**
     * @param $memoId
     * @param $orderId
     * @param $grandTotal
     * @param $count
     * @param $shippingIncTax
     * @param $baseAdjustment
     * @param $discountAmount
     * @param $invoiceCount
     * @param $orderItemIds
     * @return bool
     * @throws FileSystemException
     * @throws JsonException
     * @throws Zend_Log_Exception
     */
    private function reInvoice($memoId, $orderId, $grandTotal, $count, $shippingIncTax, $baseAdjustment, $discountAmount, $invoiceCount, $orderItemIds): bool
    {
        $grandTotal     = $grandTotal ?? 0;
        $baseAdjustment = $baseAdjustment ?? 0;
        $shippingIncTax = $shippingIncTax ?? 0;
        $createData     = [
            'credit_grand_total' => $grandTotal + $shippingIncTax,
        ];

        /** 重開發票 */
        $reInvoiceIssue  = $this->_invoiceService->reInvoiceIssue(
            $orderId,
            $createData,
            $shippingIncTax,
            $baseAdjustment,
            $discountAmount,
            self::INVOICE_COUNT + $invoiceCount,
            $orderItemIds
        );

        if ($reInvoiceIssue['code'] === '0999') {
            // reissue_invoice_retry_count 重新開立發票次數
            $this->updateCreditMemo($memoId, [
                'is_reissued_invoice'         => 1,
                'reissue_invoice_retry_count' => ++$count
            ]);
            $status = true;
        } else
        {
            $this->updateCreditMemo($memoId, [
                'reissue_invoice_retry_count' => ++$count
            ]);
        }

        return $status ?? false;
    }

    /**
     * @param $column
     * @param $data
     * @param array $except
     * @return array
     */
    private function columnToArray($column, $data, array $except = []): array
    {
        $result = [];
        foreach ($data as $item) {
            if (in_array($item[$column], $except, true)) {
                continue;
            }
            $result[] = $item[$column];
        }
        return $result;
    }

    /**
     * @param $orderId
     * @return array
     */
    protected function getEcpayInvoiceHotaiOrderInvoiceLogs($orderId): array
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()->from('ecpay_invoice_hotai_order_invoice_logs');

        $select->where('order_id = ' . $orderId);
        return $connection->fetchAll($select);
    }

    /**
     * @param $orderId
     * @return array
     */
    protected function getOrder($orderId): array
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()->from('sales_order');

        $select->where('entity_id = ' . $orderId);
        return $connection->fetchRow($select);
    }
}
