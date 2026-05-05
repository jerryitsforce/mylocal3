<?php

namespace Branch8\Catalog\Plugin\Magento\Catalog\Model;

use Magento\CatalogInventory\Model\Stock;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Http\Context as HttpContext;

class Product
{
    protected $getProductSalableQty;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var HttpContext
     */
    private $httpContext;

    public function __construct(
        \Magento\InventorySales\Model\GetProductSalableQty $getProductSalableQty,
        StockRegistryInterface $stockRegistry,
        TimezoneInterface $timezone,
        Session $customerSession,
        HttpContext $httpContext
    ){
        $this->getProductSalableQty = $getProductSalableQty;
        $this->stockRegistry = $stockRegistry;
        $this->timezone = $timezone;
        $this->customerSession = $customerSession;
        $this->httpContext = $httpContext;
    }

    /**
     * The issue salable qty = 0 but stock status is in stock have not fixed by Magento yet, so we validate again on isSalable again.
     * If this issue is fixed, remove this plugin
     * @param $subject
     * @param $result
     * @return bool
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterIsSalable($subject, $result){
        if (!$subject->getId() || !$subject->getSku() || !$subject->getTypeId()) {
            return $result;
        }

        // check LimitPurchased for Customer Group
        if(!$this->checkLimitPurchased($subject)){
            return false;
        }

        // Check custom option variation enable
        $stock = $this->stockRegistry->getStockItem($subject->getId());
        if(!$stock->getManageStock()){
            // If Manage Stock is OFF, we rely on livesearch_instock attribute
            // We ignore $result (upstream stock check) but ensure product is actually Enabled to avoid selling disabled items
            if ($subject->getStatus() == \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED) {
                return (bool)$subject->getData('livesearch_instock');
            }
            return $result;
        }
        $defaultStockId = Stock::DEFAULT_STOCK_ID;
        try{
            $salableQty = $this->getProductSalableQty->execute($subject->getData('sku'), $defaultStockId);
        }catch(\Throwable $e){
            return false;
        }
        return $result && $salableQty;
    }

    private function checkLimitPurchased($product){
        if($product->getId() && $product->getLimitPurchasedEnable()){
            $now = $this->timezone->date()->getTimestamp();
            $start_time = $product->getLimitPurchasedStartTime() ? $this->timezone->date(new \DateTime($product->getLimitPurchasedStartTime()))->getTimestamp() : '';
            $end_time = $product->getLimitPurchasedEndTime() ? $this->timezone->date(new \DateTime($product->getLimitPurchasedEndTime()))->getTimestamp() : '';
            if (($start_time && $end_time && $start_time <= $now && $now <= $end_time)
                || ($start_time && !$end_time && $start_time <= $now)
                || (!$start_time && $end_time && $end_time >= $now)
                || (!$start_time && !$end_time)) {
                if ($this->isLoggedIn() && !empty($product->getLimitPurchasedCustomerGroup())) {
                    $limitPurchasedCustomerGroup = array_filter(explode(',', $product->getLimitPurchasedCustomerGroup()));
                    if (!count($limitPurchasedCustomerGroup) ||
                        !in_array($this->getCustomerGroupId(), $limitPurchasedCustomerGroup)) {
                        return false;
                    }
                } else {
                    return false;
                }
            }
        }
        return true;
    }

    protected function getCustomerGroupId(){
        if($this->httpContext->getValue(CustomerContext::CONTEXT_GROUP)){
            return $this->httpContext->getValue(CustomerContext::CONTEXT_GROUP);
        }
        return $this->customerSession->getCustomerGroupId();
    }

    /**
     * Check Is Logged In
     *
     * @return bool
     */
    protected function isLoggedIn()
    {
        if($this->httpContext->getValue(CustomerContext::CONTEXT_AUTH)){
            return (bool)$this->httpContext->getValue(CustomerContext::CONTEXT_AUTH);
        }
        return $this->customerSession->isLoggedIn();
    }
}
