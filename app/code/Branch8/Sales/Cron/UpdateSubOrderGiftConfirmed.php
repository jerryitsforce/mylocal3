<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Branch8\Sales\Cron;

use Branch8\HotaiCore\Helper\Common as HotaiCoreCommon;
use Branch8\HotaiCore\Model\Order\Status as HotaiStatus;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder;
use Branch8\Sales\Helper\Order\UpdateOrderStatus;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Registry;

class UpdateSubOrderGiftConfirmed
{
    const LOG_PATH = 'Sales/Cron/UpdateSubOrderGiftConfirmed';

    /** @var ResourceConnection */
    protected $resource;

    /** @var ParentOrder */
    protected $parentOrderResource;

    /** @var OrderFactory */
    protected $orderFactory;

    /** @var CollectionFactory */
    protected $orderCollectionFactory;

    /** @var TransactionFactory */
    protected $transactionFactory;

    /** @var HotaiCoreCommon */
    protected $hotaiCoreCommon;

    /** @var Registry */
    protected $registry;

    /** @var UpdateOrderStatus */
    protected $updateOrderStatus;

    public function __construct(
        ResourceConnection $resource,
        ParentOrder $parentOrderResource,
        OrderFactory $orderFactory,
        CollectionFactory $orderCollectionFactory,
        TransactionFactory $transactionFactory,
        HotaiCoreCommon $hotaiCoreCommon,
        Registry $registry,
        UpdateOrderStatus $updateOrderStatus
    ) {
        $this->resource = $resource;
        $this->parentOrderResource = $parentOrderResource;
        $this->orderFactory = $orderFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->transactionFactory = $transactionFactory;
        $this->hotaiCoreCommon = $hotaiCoreCommon;
        $this->registry = $registry;
        $this->updateOrderStatus = $updateOrderStatus;
    }

    /**
     * Execute the cron
     *
     * @return void
     */
    public function execute()
    {

        $this->hotaiCoreCommon->writeLog(
            "------Start Of Cron-Update-Sub-Order-Gift-Confirmed-----",
            self::LOG_PATH
        );

        try {
            $ordersToUpdate = $this->getOrdersToUpdate();
            $this->updateOrders($ordersToUpdate);
        } catch (\Exception $e) {
            $this->hotaiCoreCommon->writeLog(
                "Error in UpdateSubOrderGiftConfirmed cron: " . $e->getMessage(),
                self::LOG_PATH
            );
        }

        $this->hotaiCoreCommon->writeLog(
            "------End Of Cron-Update-Sub-Order-Gift-Confirmed-----",
            self::LOG_PATH
        );
    }

    /**
     * Get orders that need to be updated
     * Find parent orders where is_gift_confirmed = true but sub-orders have is_gift_confirmed = false
     * and the sub-orders have invoices issued
     *
     * @return array
     */
    protected function getOrdersToUpdate()
    {
        $connection = $this->resource->getConnection();
        
        // Query to find parent orders with is_gift_confirmed = true
        // and their sub-orders with is_gift_confirmed = false and invoice issued
        $select = $connection->select()
            ->from(
                ['spoc' => $this->resource->getTableName('sales_parent_order_children')],
                [
                    'parent_id' => 'spoc.parent_id',
                    'children_id' => 'spoc.children_id'
                ]
            )
            ->joinLeft(
                ['spod' => $this->resource->getTableName('sales_parent_order_detail')],
                'spoc.parent_id = spod.parent_id',
                []
            )
            ->joinLeft(
                ['so' => $this->resource->getTableName('sales_order')],
                'spoc.children_id = so.entity_id',
                []
            )
            ->joinInner(
                ['si' => $this->resource->getTableName('sales_invoice')],
                'so.entity_id = si.order_id',
                []
            )
            ->where('spod.is_gift_confirmed = ?', 1) // Parent order is gift confirmed
            ->where('so.is_gift_confirmed = ?', 0)   // Sub order is NOT gift confirmed
            ->where('so.entity_id IS NOT NULL')      // Ensure sales order exists
            ->where('si.entity_id IS NOT NULL');     // Ensure invoice exists

        $results = $connection->fetchAll($select);
        
        $this->hotaiCoreCommon->writeLog(
            "Found " . count($results) . " orders to update (parent confirmed, sub not confirmed, invoice issued)",
            self::LOG_PATH
        );

        return $results;
    }

    /**
     * Update the orders with is_gift_confirmed = true
     *
     * @param array $ordersToUpdate
     * @return void
     */
    protected function updateOrders(array $ordersToUpdate)
    {
        if (empty($ordersToUpdate)) {
            return;
        }

        $transaction = $this->transactionFactory->create();
        $updatedCount = 0;

        foreach ($ordersToUpdate as $orderData) {
            try {
                $orderId = $orderData['children_id'];
                $parentId = $orderData['parent_id'];

                $this->hotaiCoreCommon->writeLog(
                    "Updating order ID: $orderId (parent: $parentId)",
                    self::LOG_PATH
                );

                $order = $this->orderFactory->create()->load($orderId);
                
                if (!$order->getId()) {
                    $this->hotaiCoreCommon->writeLog(
                        "Order ID $orderId not found, skipping",
                        self::LOG_PATH
                    );
                    continue;
                }

                // Update the is_gift_confirmed field
                $order->setIsGiftConfirmed(1);
                
                // Update order status to gift_info_complete
                $order->setStatus(HotaiStatus::STATUS_GIFT_INFO_COMPLETE);
                
                // Add a comment to the order history
                $comment = __('Gift confirmation status updated by cron job due to parent order confirmation.');
                $order->addCommentToStatusHistory($comment);

                // Update order items flow_status
                $this->updateOrderItems($order);

                // Update sub order address with parent order address
                $this->updateSubOrderAddress($orderId, $parentId);

                $transaction->addObject($order);
                $updatedCount++;

            } catch (\Exception $e) {
                $this->hotaiCoreCommon->writeLog(
                    "Error updating order ID {$orderData['children_id']}: " . $e->getMessage(),
                    self::LOG_PATH
                );
                continue;
            }
        }

        // Save all changes in a transaction
        if ($updatedCount > 0) {
            try {
                $transaction->save();
                $this->hotaiCoreCommon->writeLog(
                    "Successfully updated $updatedCount orders",
                    self::LOG_PATH
                );
            } catch (\Exception $e) {
                $this->hotaiCoreCommon->writeLog(
                    "Error saving transaction: " . $e->getMessage(),
                    self::LOG_PATH
                );
            }
        }
    }

    /**
     * Update order items flow_status to gift_info_complete
     *
     * @param \Magento\Sales\Model\Order $order
     * @return void
     */
    protected function updateOrderItems($order)
    {
        try {
            foreach ($order->getAllVisibleItems() as $item) {
                $item->setFlowStatus(HotaiStatus::STATUS_GIFT_INFO_COMPLETE);
                $item->save();

                // Add item status record using the helper
                $this->updateOrderStatus->addItemStatusRecord(
                    $order->getId(),
                    $item,
                    HotaiStatus::STATUS_GIFT_INFO_COMPLETE
                );
            }

            $this->hotaiCoreCommon->writeLog(
                "Updated flow_status for " . count($order->getAllVisibleItems()) . " items in order ID: " . $order->getId(),
                self::LOG_PATH
            );

        } catch (\Exception $e) {
            $this->hotaiCoreCommon->writeLog(
                "Error updating order items for order ID {$order->getId()}: " . $e->getMessage(),
                self::LOG_PATH
            );
        }
    }

    /**
     * Update sub order address with parent order address
     *
     * @param int $subOrderId
     * @param int $parentOrderId
     * @return void
     */
    protected function updateSubOrderAddress($subOrderId, $parentOrderId)
    {
        try {
            $connection = $this->resource->getConnection();
            
            // Get parent order addresses
            $parentAddressSelect = $connection->select()
                ->from($this->resource->getTableName('sales_parent_order_address'))
                ->where('parent_order_id = ?', $parentOrderId);
            
            $parentAddresses = $connection->fetchAll($parentAddressSelect);
            
            if (empty($parentAddresses)) {
                $this->hotaiCoreCommon->writeLog(
                    "No parent addresses found for parent order ID: $parentOrderId",
                    self::LOG_PATH
                );
                return;
            }

            // Update sub order addresses with parent order address data
            foreach ($parentAddresses as $parentAddress) {
                $addressType = $parentAddress['address_type'];
                
                // Prepare address data to update
                $addressData = [
                    'customer_id' => $parentAddress['customer_id'],
                    'region' => $parentAddress['region'],
                    'region_id' => $parentAddress['region_id'],
                    'postcode' => $parentAddress['postcode'],
                    'lastname' => $parentAddress['lastname'],
                    'street' => $parentAddress['street'],
                    'city' => $parentAddress['city'],
                    'email' => $parentAddress['email'],
                    'telephone' => $parentAddress['telephone'],
                    'country_id' => $parentAddress['country_id'],
                    'firstname' => $parentAddress['firstname'],
                    'middlename' => $parentAddress['middlename'],
                    'prefix' => $parentAddress['prefix'],
                    'suffix' => $parentAddress['suffix'],
                    'company' => $parentAddress['company'],
                    'fax' => $parentAddress['fax'],
                    'customer_address_id' => $parentAddress['customer_address_id'],
                    'vat_id' => $parentAddress['vat_id']
                ];

                // Update the sub order address
                $updateCondition = [
                    'parent_id = ?' => $subOrderId,
                    'address_type = ?' => $addressType
                ];

                $connection->update(
                    $this->resource->getTableName('sales_order_address'),
                    $addressData,
                    $updateCondition
                );

                $this->hotaiCoreCommon->writeLog(
                    "Updated $addressType address for sub order ID: $subOrderId with parent order address data",
                    self::LOG_PATH
                );
            }

        } catch (\Exception $e) {
            $this->hotaiCoreCommon->writeLog(
                "Error updating address for sub order ID $subOrderId: " . $e->getMessage(),
                self::LOG_PATH
            );
        }
    }
}
