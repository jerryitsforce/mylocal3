<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\OptionsWithStockAndImages\Plugin\Webkul\OptionsWithStockAndImages\Observer;

class RefundOrderInventoryObserver
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

    public function aroundCheckReturnToStock(
        \Webkul\OptionsWithStockAndImages\Observer\RefundOrderInventoryObserver $subject,
        \Closure $proceed,
        $creditmemo
    )
    {
        // This class has been removed.
        $backStockQty = [];
        $message = __('Product restocked after credit memo creation (credit memo: %1)', $creditmemo->getId());
        foreach ($creditmemo->getItems() as $item) {
            $comb = "";
            $itemOptions = [];
            $orderItem = $this->orderItemRepository->get($item->getOrderItemId());
            $product = $item->getProduct();
            $productRowId = $product->getRowId();
            //$optionData = $this->helper->getOptionData($productId);
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
                    foreach ($options as $key => $value) {
                        if (isset($optionData[$key]) && isset($optionData[$key][$value])) {
                            $comb .= $optionData[$key][$value] . "_";
                        }
                    }
                    $comb = trim($comb, "_");
                    if ($comb) {
                        $variation = $this->helper->getCombData($productRowId, $comb);
                        if ($variation->getId()) {
                            $this->logger->info('RefundOrder');
                            $this->logger->info(json_encode([
                                'comb' => $comb,
                                'stock' => $variation->getStock(),
                                'qtyRefund' => $item->getQty()
                            ]));
                            if ($variation->getIsSync()) {
                                if (array_key_exists($variation->getSku(), $backStockQty)) {
                                    $backStockQty[$variation->getSku()] = (int)$backStockQty[$variation->getSku()] + $item->getQty();
                                } else {
                                    $backStockQty[$variation->getSku()] = $item->getQty();
                                }
                            } else {
                                $variation->setStock($variation->getStock() + $item->getQty());
                                $this->saveObj($variation);
                            }
                        }
                    }
                }
            }
        }
        $this->backStockQty($backStockQty, $message);
    }

    private function backStockQty($skus, $message = ''){
        if(count($skus)){
            foreach($skus as $sku => $qty){
                $this->salable->backStockQty($sku, $qty, $message);
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
