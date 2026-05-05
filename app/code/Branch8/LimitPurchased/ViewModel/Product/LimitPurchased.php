<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\LimitPurchased\ViewModel\Product;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\ScopeInterface;

class LimitPurchased implements ArgumentInterface
{
    const XML_PATH_LIMIT_PURCHASED_ENABLED = 'limit_purchased/general/enabled';
    const XML_PATH_LIMIT_PURCHASED_ORDER_STATUSES = 'limit_purchased/general/order_statuses';

    /**
     * @var HttpContext
     */
    private $httpContext;

    /**
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param HttpContext $httpContext
     * @param CustomerSession $customerSession
     * @param TimezoneInterface $timezone
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ScopeConfigInterface $scopeConfig
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        HttpContext $httpContext,
        CustomerSession $customerSession,
        TimezoneInterface $timezone,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ScopeConfigInterface $scopeConfig,
        ResourceConnection $resourceConnection
    ) {
        $this->httpContext = $httpContext;
        $this->customerSession = $customerSession;
        $this->timezone = $timezone;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Get max allowed quantity for product
     *
     * @param ProductInterface $product
     * @return int|null
     */
    public function getMaxAllowedQty(ProductInterface $product): ?int
    {
        if (!$this->scopeConfig->isSetFlag(self::XML_PATH_LIMIT_PURCHASED_ENABLED, ScopeInterface::SCOPE_STORE)) {
            return null;
        }

        if(!$this->isLoggedIn())
        {
            return null;
        }

        $data = [
            'is_enabled' => $product->getLimitPurchasedEnable(),
            'start_time' => $product->getLimitPurchasedStartTime(),
            'end_time' => $product->getLimitPurchasedEndTime(),
            'customer_group_ids' => $product->getLimitPurchasedCustomerGroup(),
            'limit_qty' => $product->getLimitPurchasedQty(),
            'sku' => $product->getSku(),
            'product_id' => $product->getId()
        ];
        
        return $this->getMaxAllowedQtyFromData($data);
    }

    /**
     * Get max allowed quantity from data array
     *
     * @param array $data
     * @return int|null
     */
    public function getMaxAllowedQtyFromData(array $data): ?int
    {
        if (empty($data['is_enabled'])) {
            return null;
        }

        $now = $this->timezone->date()->getTimestamp();
        $startTime = !empty($data['start_time']) ? $this->timezone->date(new \DateTime($data['start_time']))->getTimestamp() : null;
        $endTime = !empty($data['end_time']) ? $this->timezone->date(new \DateTime($data['end_time']))->getTimestamp() : null;

        $isTimeValid = ($startTime && $endTime && $startTime <= $now && $now <= $endTime)
            || ($startTime && !$endTime && $startTime <= $now)
            || (!$startTime && $endTime && $endTime >= $now)
            || (!$startTime && !$endTime);

        if (!$isTimeValid) {
            return null;
        }

        if ($this->isLoggedIn() && !empty($data['customer_group_ids'])) {
            $limitPurchasedCustomerGroup = array_filter(explode(',', $data['customer_group_ids']));
            if (count($limitPurchasedCustomerGroup) && !in_array($this->getCustomerGroupId(), $limitPurchasedCustomerGroup)) {
                return -1;
            }
        } elseif (!$this->isLoggedIn() && !empty($data['customer_group_ids'])) {
             return -1;
        }

        if (!empty($data['limit_qty'])) {
            $limitQty = (int)$data['limit_qty'];
            $customerId = (int)$this->httpContext->getValue('customer_id');
            if (empty($data['product_id'])) {
                 return $limitQty; 
            }
            
            $qtyOrdered = $this->getQtyOrderedFromOrderHistory($data['product_id'], $customerId, $data['start_time'] ?? null, $data['end_time'] ?? null);
            $remainingQty = $limitQty - $qtyOrdered;
            return $remainingQty > 0 ? $remainingQty : 0;
        }

        return null;
    }

    /**
     * Get QtyOrdered from order history by product id.
     *
     * @param int $productId
     * @param int $customerId
     * @param string|null $startTime
     * @param string|null $endTime
     * @return int
     */
    private function getQtyOrderedFromOrderHistory($productId, $customerId, $startTime = null, $endTime = null): int
    {
        if (!$customerId) {
            return 0;
        }

        $limitPurchasedOrderStatuses = $this->scopeConfig->getValue(self::XML_PATH_LIMIT_PURCHASED_ORDER_STATUSES);
        if (empty($limitPurchasedOrderStatuses)) {
            return 0;
        }

        $limitPurchasedOrderStatuses = explode(',', $limitPurchasedOrderStatuses);
        $limitPurchasedOrderStatuses = array_filter($limitPurchasedOrderStatuses);

        if (empty($limitPurchasedOrderStatuses)) {
            return 0;
        }

        $connection = $this->resourceConnection->getConnection();
        $salesOrderTable = $this->resourceConnection->getTableName('sales_order');
        $salesOrderItemTable = $this->resourceConnection->getTableName('sales_order_item');

        $select = $connection->select()
            ->from(['soi' => $salesOrderItemTable], [new \Zend_Db_Expr('SUM(soi.qty_ordered)')])
            ->join(['so' => $salesOrderTable], 'so.entity_id = soi.order_id', [])
            ->where('so.customer_id = ?', $customerId)
            ->where('soi.product_id = ?', $productId)
            ->where('so.status IN (?)', $limitPurchasedOrderStatuses);

        if ($startTime) {
            $select->where('so.created_at >= ?', $startTime);
        }
        if ($endTime) {
            $select->where('so.created_at <= ?', $endTime);
        }

        $qtyOrdered = (int)$connection->fetchOne($select);

        return $qtyOrdered;
    }

    /**
     * Get Customer Group ID
     * @return int
     */
    private function getCustomerGroupId()
    {
        if ($this->httpContext->getValue(CustomerContext::CONTEXT_GROUP)) {
            return (int)$this->httpContext->getValue(CustomerContext::CONTEXT_GROUP);
        }
        return (int)$this->customerSession->getCustomerGroupId();
    }

    /**
     * Check Is Logged In
     *
     * @return bool
     */
    private function isLoggedIn()
    {
        if ($this->httpContext->getValue(CustomerContext::CONTEXT_AUTH)) {
            return (bool)$this->httpContext->getValue(CustomerContext::CONTEXT_AUTH);
        }
        return $this->customerSession->isLoggedIn();
    }
}
