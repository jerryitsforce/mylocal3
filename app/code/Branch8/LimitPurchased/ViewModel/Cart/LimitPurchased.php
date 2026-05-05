<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\LimitPurchased\ViewModel\Cart;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Branch8\LimitPurchased\ViewModel\Product\LimitPurchased as ProductLimitPurchased;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class LimitPurchased implements ArgumentInterface
{
    const XML_PATH_LIMIT_PURCHASED_ENABLED = 'limit_purchased/general/enabled';

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var ProductLimitPurchased
     */
    private $productLimitPurchased;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param CheckoutSession $checkoutSession
     * @param ProductLimitPurchased $productLimitPurchased
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        ProductLimitPurchased $productLimitPurchased,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->productLimitPurchased = $productLimitPurchased;
        $this->resourceConnection = $resourceConnection;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @var array
     */
    private $_cache = null;

    /**
     * Get limits for all items in cart
     *
     * @return array
     */
    public function getItemLimits(): array
    {
        if ($this->_cache !== null) {
            return $this->_cache;
        }
        if (!$this->scopeConfig->isSetFlag(self::XML_PATH_LIMIT_PURCHASED_ENABLED, ScopeInterface::SCOPE_STORE)) {
            return [
                'limits' => [],
                'products' => []
            ];
        }

        $limits = [];
        $productMap = [];
        $quote = $this->checkoutSession->getQuote();

        $items = $quote->getAllItems();

        if (empty($items)) {
            return [
                'limits' => [],
                'products' => []
            ];
        }

        $productIds = [];
        $itemMap = [];
        foreach ($items as $item) {
            $pid = $item->getProductId();
            if ($item->getParentItem()) {
                $pid = $item->getParentItem()->getProductId();
            }
            $productIds[] = $pid;
            // Support multiple items with same product ID (e.g. configurable variations)
            $itemMap[$pid][] = $item->getId();
        }

        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('branch8_limit_purchased_index');
        
        $select = $connection->select()
            ->from($tableName)
            ->where('product_id IN (?)', $productIds);
            
        $results = $connection->fetchAll($select);

        foreach ($results as $row) {
            $maxQty = $this->productLimitPurchased->getMaxAllowedQtyFromData($row);
            
            if (isset($itemMap[$row['product_id']])) {
                foreach ($itemMap[$row['product_id']] as $itemId) {
                    $productMap[$itemId] = $row['product_id'];
                    
                    if ($maxQty !== null) {
                        $limits[$itemId] = (int)$maxQty;
                    }
                }
            }
        }

        $this->_cache = [
            'limits' => $limits,
            'products' => $productMap
        ];

        return $this->_cache;
    }
}
