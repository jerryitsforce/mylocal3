<?php
declare(strict_types=1);

namespace Branch8\WebkulMpsplitorder\Model\Actions;

use Branch8\MarketplaceParentOrderRetryCancel\Model\FailedRecordFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;

class ProcessParentOrderFailed
{
    const PARENT_ORDER_STATUS_FAILED = 'parent_order_failed';
    const LOG_FOLDER_NAME = 'WebkulMpsplitorder/ProcessParentOrderFailed';

    private ResourceConnection $resourceConnection;

    private \Magento\Sales\Model\ResourceModel\Order $orderResourceModel;
    private \Magento\Sales\Model\OrderFactory $orderFactory;

    private LoggerInterface $logger;
    private FailedRecordFactory $failedRecordFactory;
    private HotaiCoreCommonHelper $hotaiCoreCommonHelper;

    /**
     * @param ResourceConnection $resourceConnection
     * @param \Magento\Sales\Model\ResourceModel\Order $resourceModel
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param FailedRecordFactory $failedRecordFactory
     * @param LoggerInterface $logger
     * @param HotaiCoreCommonHelper $hotaiCoreCommonHelper
     */
    public function __construct(
        ResourceConnection                       $resourceConnection,
        \Magento\Sales\Model\ResourceModel\Order $resourceModel,
        \Magento\Sales\Model\OrderFactory        $orderFactory,
        FailedRecordFactory                      $failedRecordFactory,
        LoggerInterface                          $logger,
        HotaiCoreCommonHelper                    $hotaiCoreCommonHelper
    )
    {
        $this->resourceConnection = $resourceConnection;
        $this->orderResourceModel = $resourceModel;
        $this->orderFactory = $orderFactory;
        $this->failedRecordFactory = $failedRecordFactory;
        $this->logger = $logger;
        $this->hotaiCoreCommonHelper = $hotaiCoreCommonHelper;
    }

    /**
     * @param \Webkul\Mpsplitorder\Model\Mpsplitorder $parentOrder
     * @param array $childOrderIds
     * @return void
     * https://branch8.atlassian.net/browse/HTGO2-2219
     * 1.When the parent order creation fails:
     * - The status of the child order should change to "Parent Order Failed".
     * 2.Add a schedule (updates every 5 minutes):
     * Case 1:if the parent order has been established:
     * - Delete the parent order.
     * - Recreate the parent order.
     * - Create a EC-PAY invoice (if have payment flow).
     * - Create a native Magento invoice.
     * - Cancel the order.
     * - Create a Credit memo
     * - Refund points.
     * Case 2:If the parent order has not been established:
     * - Recreate the parent order:
     * - Create a EC-PAY  invoice (if have payment flow).
     * - Create a native Magento invoice.
     * - Cancel the order.
     * - Create a Credit memo.
     * - Refund points.
     */
    public function process(\Webkul\Mpsplitorder\Model\Mpsplitorder $parentOrder, Quote $masterQuote, array $childOrderIds = [])
    {
        if (empty($childOrderIds) || !$parentOrder->getId()) {
            $this->writeLogInternal(json_encode([
                "Title" => "ProcessParentOrderFailed: Early return when process parent order failed",
                "Master Quote ID" => $masterQuote->getId(),
                "MasterQuote Customer ID" => $masterQuote->getCustomerId(),
                "Child Order IDs" => join(',', $childOrderIds),
                "Parent Order ID" => $parentOrder->getId(),
            ]), self::LOG_FOLDER_NAME);

            return;
        }

        foreach ($childOrderIds as $childOrderId) {
            try {
                $order = $this->orderFactory->create()->load($childOrderId);
                if (!$order->getIncrementId()) {
                    continue;
                }
                $order->setStatus(self::PARENT_ORDER_STATUS_FAILED);
                $order->setData('reserve_hotai_parent_order_number', $parentOrder->getData('hotai_reserved_order_id'));
                $this->orderResourceModel->save($order);
            } catch (\Exception $exception) {
                $this->writeLogInternal(json_encode([
                    "Title" => "ProcessParentOrderFailed: Error when looping through child orders to process parent order failed",
                    "Exception Message" => $exception->getMessage(),
                    "Child Order ID" => $childOrderId,
                    "Child Order IDs" => join(',', $childOrderIds),
                    "Master Quote ID" => $masterQuote->getId(),
                    "MasterQuote Customer ID" => $masterQuote->getCustomerId(),
                    "Parent Order ID" => $parentOrder->getId(),
                    "Exception Trace" => $exception->getTraceAsString(),
                ]), self::LOG_FOLDER_NAME);

                continue;
            }
        }

        try {
            $failedRecord = $this->failedRecordFactory->create([
                'data' => [
                    'old_parent_order_id' => $parentOrder->getData('hotai_reserved_order_id'),
                    'sub_order_ids' => join(',', $childOrderIds),
                    'master_quote_id' => $masterQuote->getId()
                ]
            ])->setDataChanges(true);
            $failedRecord->getResource()->save($failedRecord);
        } catch (\Exception $exception) {
            $this->writeLogInternal(json_encode([
                "Title" => "ProcessParentOrderFailed: Error when creating failed record",
                "Old Parent Order ID" => $parentOrder->getData('hotai_reserved_order_id'),
                "Exception Message" => $exception->getMessage(),
                "Master Quote ID" => $masterQuote->getId(),
                "MasterQuote Customer ID" => $masterQuote->getCustomerId(),
                "Child Order IDs" => join(',', $childOrderIds),
                "Parent Order ID" => $parentOrder->getId(),
                "Exception Trace" => $exception->getTraceAsString(),
            ]), self::LOG_FOLDER_NAME);
        }
    }

    protected function writeLogInternal(string|array $message, string $folderName, string $fileName = ""){
        if (\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_WebkulMpsplitorder', 'ProcessParentOrderFailed')) {
            $this->hotaiCoreCommonHelper->writeLog($message, $folderName, $fileName);
        }
    }
}
