<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\OptionsWithStockAndImages\Rewrite\Webkul\OptionsWithStockAndImages\Observer;

class RefundOrderInventoryObserver extends \Webkul\OptionsWithStockAndImages\Observer\RefundOrderInventoryObserver
{
    /**
     * @var \Webkul\OptionsWithStockAndImages\Helper\Data
     */
    public $helper;

    /**
     * @var \Magento\Sales\Api\OrderItemRepositoryInterface
     */
    public $orderItemRepository;

    /**
     * @var \Webkul\OptionsWithStockAndImages\Logger\Logger
     */
    public $logger;

    /**
     * @var \Branch8\OptionsWithStockAndImages\Helper\Salable
     */
    protected $salable;

    /**
     * Constructor
     *
     * @param \Webkul\OptionsWithStockAndImages\Helper\Data $helper
     * @param \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository
     * @param \Webkul\OptionsWithStockAndImages\Logger\Logger $logger
     */
    public function __construct(
        \Webkul\OptionsWithStockAndImages\Helper\Data $helper,
        \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository,
        \Webkul\OptionsWithStockAndImages\Logger\Logger $logger,
        \Branch8\OptionsWithStockAndImages\Helper\Salable $salable
    ) {
        $this->helper = $helper;
        $this->orderItemRepository = $orderItemRepository;
        $this->logger = $logger;
        $this->salable = $salable;
    }

    /**
     * Main function
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        try {
            $creditmemo = $observer->getEvent()->getCreditmemo();

            if ($creditmemo->getIssueType() == \Branch8\Sales\Model\CreditMemo\IssueType::RETURN) {
                // Triplicate Company Invoice : should not return qty as issuing creditmemo
                $order = $creditmemo->getOrder();
                $ecpayInvoiceType = $order->getEcpayInvoiceType();
                if(!empty($ecpayInvoiceType) && 
                    $ecpayInvoiceType == \Ecpay\General\Model\EcpayInvoice::ECPAY_INVOICE_TYPE_C){
                    return $this;
                }
            }
            
            

            $this->checkReturnToStock($creditmemo);
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }

    private function checkReturnToStock($creditmemo)
    {
        $backStockQty = [];
        $messages = [];
        foreach ($creditmemo->getItems() as $item) {
            $comb = "";
            $itemOptions = [];
            $orderItem = $this->orderItemRepository->get($item->getOrderItemId());
            $product = $orderItem->getProduct();
            if(!$product){
                continue;
            }
            if(!$item->getBackToStock() || !($item->getQty() > 0)){
                continue;
            }
            $productRowId = $product->getRowId();
            //$optionData = $this->helper->getOptionData($productId);
            $optionData = [];
            $msg = [];
            $msg[] = __('order: %1', $creditmemo->getOrder()->getIncrementId());
            foreach ($product->getOptions() as $option) {
                $optType = $option->getType();
                if ($optType == "drop-down" || $optType == "drop_down" || $optType == "radio") {
                    $optionId = $option->getId();
                    $optionData[$optionId] = [];
                    foreach ($option->getValues() as $value) {
                        $valueId = $value->getId();
                        $optionData[$optionId][$valueId] = $value->getDefaultTitle();
                    }
                }
            }
            if (!empty($optionData)) {
                $options = $orderItem->getProductOptions();
                $msgCO = [];
                if (isset($options['options']) && !empty($options['options'])) {
                    foreach ($options['options'] as $option) {
                        if(!isset($optionData[$option['option_id']])){
                            continue;
                        }
                        $optDataArr = $optionData[$option['option_id']];
                        if (isset($optDataArr) && isset($optDataArr[$option['option_value']])) {
                            $comb .= $optDataArr[$option['option_value']] . "_";
                            $msgCO[] = $option['label'] . ' : ' . $option['value'];
                        }
                    }
                }
                if(count($msgCO)){
                    $msg[] = __('Custom option: %1', implode(' , ' , $msgCO));
                }
                
                $comb = trim($comb, "_");
                $variation = $this->helper->getCombData($productRowId, $comb);
                if ($variation->getId()) {
                    $this->logger->info('RefundOrder');
                    $this->logger->info(json_encode([
                        'comb' => $comb,
                        'stock' => $variation->getStock(),
                        'qtyRefund' => $item->getQty()
                    ]));
                    $qty = ((int)$orderItem->getQtyOrdered() - (int)$orderItem->getQtyRefunded() > 0) ? $item->getQty() : 0;
                     
                    if ($variation->getIsSync()) {
                        if (array_key_exists($variation->getSku(), $backStockQty)) {
                            $backStockQty[$variation->getSku()] = (int)$backStockQty[$variation->getSku()] + $qty;
                        } else {
                            $backStockQty[$variation->getSku()] = $qty;
                        }
                        $msg[] = __('Variation: %1', $comb);
                        $messages[$variation->getSku()] = implode(' - ', $msg);
                    } else {
                        $variation->setStock($variation->getStock() + $qty);
                        $this->saveObj($variation);
                    }

                    $qtyToShip = $variation->getReadyToShipQty() - $qty;
                    if ($qtyToShip < 0) {
                        $qtyToShip = 0;
                    }
                    $variation->setReadyToShipQty($qtyToShip);
                    $this->saveObj($variation);
                }
            }
            $this->salable->syncNeedToRefill([$product->getId()]);
        }
        $order = $creditmemo->getOrder();
        $this->backStockQty($backStockQty, $order, $messages);
    }

    private function backStockQty($skus, $order, $messages){
        if(count($skus)){
            foreach($skus as $sku => $qty){
                $message = __('Product restocked after credit memo creation (order: %1)', $order->getIncrementId()); 
                if(isset($messages[$sku])){
                    $message = __('Product restocked after credit memo creation (%1)', $messages[$sku]); 
                }
                $this->salable->backStockQty($sku, $qty, $order, $message);
            }
        }
    }

    /**
     * Save Obj
     *
     * @param \Webkul\OptionsWithStockAndImages\Model\Variations $object
     * @return void
     */
    private function saveObj($object)
    {
        $object->save();
    }
}
