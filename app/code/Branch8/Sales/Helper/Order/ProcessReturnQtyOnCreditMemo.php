<?php

namespace Branch8\Sales\Helper\Order;

use Magento\InventoryCatalogApi\Model\GetProductTypesBySkusInterface;
use Magento\InventoryConfigurationApi\Model\IsSourceItemManagementAllowedForProductTypeInterface;
use Magento\InventorySalesApi\Model\GetSkuFromOrderItemInterface;
use Magento\InventorySalesApi\Model\ReturnProcessor\ProcessRefundItemsInterface;
use Magento\InventorySalesApi\Model\ReturnProcessor\Request\ItemsToRefundInterfaceFactory;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\SalesInventory\Model\Order\ReturnProcessor;
use Ecpay\General\Model\EcpayInvoice;
use Magento\Sales\Model\OrderFactory;
use Branch8\OptionsWithStockAndImages\Helper\Salable;
use Webkul\OptionsWithStockAndImages\Helper\Data as WebkulHelperData;

class ProcessReturnQtyOnCreditMemo
{
    /**
     * @var GetSkuFromOrderItemInterface
     */
    private $getSkuFromOrderItem;

    /**
     * @var ItemsToRefundInterfaceFactory
     */
    private $itemsToRefundFactory;

    /**
     * @var ProcessRefundItemsInterface
     */
    private $processRefundItems;

    /**
     * @var IsSourceItemManagementAllowedForProductTypeInterface
     */
    private $isSourceItemManagementAllowedForProductType;

    /**
     * @var GetProductTypesBySkusInterface
     */
    private $getProductTypesBySkus;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var Salable
     */
    protected $salable;

    /**
     * @var \Webkul\OptionsWithStockAndImages\Helper\Data
     */
    protected $helper;

    /**
     * @param GetSkuFromOrderItemInterface $getSkuFromOrderItem
     * @param ItemsToRefundInterfaceFactory $itemsToRefundFactory
     * @param ProcessRefundItemsInterface $processRefundItems
     * @param IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType
     * @param GetProductTypesBySkusInterface $getProductTypesBySkus
     */
    public function __construct(
        GetSkuFromOrderItemInterface $getSkuFromOrderItem,
        ItemsToRefundInterfaceFactory $itemsToRefundFactory,
        ProcessRefundItemsInterface $processRefundItems,
        IsSourceItemManagementAllowedForProductTypeInterface $isSourceItemManagementAllowedForProductType,
        GetProductTypesBySkusInterface $getProductTypesBySkus,
        OrderFactory $orderFactory,
        Salable $salable,
        WebkulHelperData $webkulHelperData
    ) {
        $this->getSkuFromOrderItem = $getSkuFromOrderItem;
        $this->itemsToRefundFactory = $itemsToRefundFactory;
        $this->processRefundItems = $processRefundItems;
        $this->isSourceItemManagementAllowedForProductType = $isSourceItemManagementAllowedForProductType;
        $this->getProductTypesBySkus = $getProductTypesBySkus;
        $this->orderFactory = $orderFactory;
        $this->salable = $salable;
        $this->helper = $webkulHelperData;
    }

    public function processReturnQtyOnCreditMemo($orderId, $memoId = false){
        // Only apply for order Triplicate Company Invoice
        $order = $this->orderFactory->create()->load($orderId);
        $ecpayInvoiceType = $order->getEcpayInvoiceType();
        if(!empty($ecpayInvoiceType) && $ecpayInvoiceType == EcpayInvoice::ECPAY_INVOICE_TYPE_C)
        {
            $creditmemos = $order->getCreditmemosCollection();
            foreach($creditmemos as $creditmemo){
                if($memoId) {
                    if($memoId != $creditmemo->getId()) {
                        continue;
                    }
                }

                $items = [];
                $returnToStockItems = [];
                $backStockQty = [];
                $messages = [];
                foreach ($creditmemo->getItems() as $item) {
                    $returnToStockItems[] = $item->getOrderItemId();
                    $orderItem = $item->getOrderItem();
                    $itemSku = $this->getSkuFromOrderItem->execute($orderItem);

                    if ($this->isValidItem($itemSku, $orderItem->getProductType())) {
                        $qty = (float)$item->getQty();
                        $processedQty = $orderItem->getQtyInvoiced() - $orderItem->getQtyRefunded() + $qty;
                        $items[$itemSku] = [
                            'qty' => ($items[$itemSku]['qty'] ?? 0) + $qty,
                            'processedQty' => ($items[$itemSku]['processedQty'] ?? 0) + (float)$processedQty,
                        ];
                    }

                    // Restock for custom option variation
                    $comb = "";
                    $itemOptions = [];
                    $product = $orderItem->getProduct();
                    $msg = [];
                    $msg[] = __('order: %1', $creditmemo->getOrder()->getIncrementId());
                    if($product){
                        $productRowId = $product->getRowId();
                        $optionData = [];
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
                            $infoBuyRequest = $orderItem->getProductOptions('info_buyRequest');
                            if (isset($infoBuyRequest['info_buyRequest']['options'])) {
                                $itemOptions = $infoBuyRequest['info_buyRequest']['options'];
                            }

                            if (!empty($itemOptions)) {
                                $options = $itemOptions;
                                $msgCO = [];
                                foreach ($options as $key => $value) {
                                    if (isset($optionData[$key]) && isset($optionData[$key][$value])) {
                                        $comb .= $optionData[$key][$value] . "_";
                                        $msgCO[] = $option['label'] . ' : ' . $option['value'];
                                    }
                                }
                                if(count($msgCO)){
                                    $msg[] = __('Custom option: %1', implode(' , ' , $msgCO));
                                }
                                $comb = trim($comb, "_");
                                if ($comb) {
                                    $variation = $this->helper->getCombData($productRowId, $comb);
                                    if ($variation->getId()) {
                                        if ($variation->getIsSync()) {
                                            if (array_key_exists($variation->getSku(), $backStockQty)) {
                                                $backStockQty[$variation->getSku()] = (int)$backStockQty[$variation->getSku()] + $item->getQty();
                                            } else {
                                                $backStockQty[$variation->getSku()] = $item->getQty();
                                            }
                                            $msg[] = __('Variation: %1', $comb);
                                            $messages[$variation->getSku()] = implode(' - ', $msg);
                                        } else {
                                            $variation->setStock($variation->getStock() + $item->getQty());
                                        }
                                        
                                        $qtyToShip = $variation->getReadyToShipQty() - $item->getQty();
                                        if ($qtyToShip < 0) {
                                            $qtyToShip = 0;
                                        }
                                        $variation->setReadyToShipQty($qtyToShip);
                                        $this->saveObj($variation);
                                    }
                                }
                            }
                        }
                    }
                }

                // Restock for custom option variation
                $this->backStockQty($backStockQty, $creditmemo->getOrder(), $messages);
    
                $itemsToRefund = [];
                foreach ($items as $sku => $data) {
                    $itemsToRefund[] = $this->itemsToRefundFactory->create([
                        'sku' => $sku,
                        'qty' => $data['qty'],
                        'processedQty' => $data['processedQty'],
                    ]);
                }
                $this->processRefundItems->execute($order, $itemsToRefund, $returnToStockItems);
            }
        }
    }

    /**
     * Verify is item valid for return qty to stock.
     *
     * @param string $sku
     * @param string|null $typeId
     * @return bool
     */
    private function isValidItem(string $sku, ?string $typeId): bool
    {
        //TODO: https://github.com/magento-engcom/msi/issues/1761
        // If product type located in table sales_order_item is "grouped" replace it with "simple"
        if ($typeId === 'grouped') {
            $typeId = 'simple';
        }

        $productType = $typeId ?: $this->getProductTypesBySkus->execute(
            [$sku]
        )[$sku];

        return $this->isSourceItemManagementAllowedForProductType->execute($productType);
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
