<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\OptionsWithStockAndImages\Plugin\Webkul\OptionsWithStockAndImages\Observer;

use Ecpay\General\Model\EcpayInvoice;

class OrderCancel
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

    public function aroundExecute(
        \Webkul\OptionsWithStockAndImages\Observer\OrderCancel $subject,
        \Closure $proceed,
        \Magento\Framework\Event\Observer $observer
    ) {
        try {
            $order = $observer->getOrder();

            // Triplicate Company Invoice : should not return qty
            //$ecpayInvoiceType = $order->getEcpayInvoiceType();
            //if(!empty($ecpayInvoiceType) && 
            //    $ecpayInvoiceType == EcpayInvoice::ECPAY_INVOICE_TYPE_C){
            //    return $this;
            //}

            $backStockQty = [];
            $messages = [];
            foreach ($order->getAllVisibleItems() as $item) {
                $comb = "";
                $itemOptions = [];
                $orderItem = $this->orderItemRepository->get($item->getId());
                if(!$item->getProduct()){
                    continue;
                }
                $product = $item->getProduct();
                $productRowId = $product->getRowId();
                //$optionData = $this->helper->getOptionData($productId);
                $optionData = [];
                $msg = [];
                $msg[] = __('order: %1', $order->getIncrementId());
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
                        $itemQty = $item->getQtyOrdered();
                        $this->logger->info('OrderCancel');
                        $this->logger->info(json_encode([
                            'comb' => $comb,
                            'stock' => $variation->getStock(),
                            'qtyCanceled' => $itemQty
                        ]));

                        $msg[] = __('Variation: %1', $comb);

                        if ($variation->getIsSync()) {
                            if (array_key_exists($variation->getSku(), $backStockQty)) {
                                $backStockQty[$variation->getSku()] = (int)$backStockQty[$variation->getSku()] + $itemQty;
                            } else {
                                $backStockQty[$variation->getSku()] = $itemQty;
                            }
                            
                            $messages[$variation->getSku()] = implode(' - ', $msg);
                        } else {
                            $variation->setStock($variation->getStock() + $itemQty);
                            $this->saveObj($variation);
                        }

                        $qtyToShip = $variation->getReadyToShipQty() - $itemQty;
                        if ($qtyToShip < 0) {
                            $qtyToShip = 0;
                        }
                        $variation->setReadyToShipQty($qtyToShip);
                        $this->saveObj($variation);

                        // Log the stock movement
                        if (count($msg)) {
                            $message = __('Product restocked after cancel order (%1)', implode(' - ', $msg));
                            $stockItem = $this->salable->getProductStockItem($product->getId());
                            $qty = (int)$stockItem->getQty() + $itemQty;
                            $this->salable->saveStockMovementLog($stockItem, $stockItem->getQty(), $qty, $message);
                        }
                    }
                }
                $this->salable->syncNeedToRefill([$product->getId()]);
            }
            $this->backStockQty($backStockQty, $order, $messages);
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }

    private function backStockQty($skus, $order, $messages){
        if(count($skus)){
            foreach($skus as $sku => $qty){
                $message = __('Product restocked after cancel order (order: %1)', $order->getIncrementId()); 
                if(isset($messages[$sku])){
                    $message = __('Product restocked after cancel order (%1)', $messages[$sku]); 
                }
                $this->salable->backStockQty($sku, $qty, $order, $message);
            }
        }
    }

    /**
     * Save object
     *
     * @param \Webkul\OptionsWithStockAndImages\Model\Variations $object
     * @return void
     */
    private function saveObj($object)
    {
        $object->save();
    }
}
