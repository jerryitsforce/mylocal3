<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\OptionsWithStockAndImages\Plugin\Webkul\OptionsWithStockAndImages\Observer;

use Branch8\OptionsWithStockAndImages\Model\Actions\GetOrderItemCombo;
use Branch8\OptionsWithStockAndImages\Model\Actions\GetSaleOrderItemCombo;
use Branch8\OptionsWithStockAndImages\Model\Actions\IsProductPreorder;
use Branch8\OptionsWithStockAndImages\Model\Actions\IsVirtualProduct;
use Branch8\OptionsWithStockAndImages\Model\Actions\NormalizeCombo;
use Magento\Framework\App\ResourceConnection;
use Branch8\OptionsWithStockAndImages\Helper\Salable;

class CheckoutSubmitAllAfter
{
    /**
     * @var \Webkul\OptionsWithStockAndImages\Helper\Data
     */
    public $helper;

    /**
     * @var \Webkul\OptionsWithStockAndImages\Logger\Logger
     */
    public $logger;

    /** @var \Magento\Framework\App\ResourceConnection */
    protected $resource;

    /**
     * @var \Branch8\OptionsWithStockAndImages\Helper\Salable
     */
    protected $salable;
    private IsProductPreorder $isProductPreorder;
    private GetOrderItemCombo $getSaleOrderItemCombo;

    /**
     * @param \Webkul\OptionsWithStockAndImages\Helper\Data $helper
     * @param \Webkul\OptionsWithStockAndImages\Logger\Logger $logger
     * @param GetOrderItemCombo $getOrderItemCombo
     * @param IsProductPreorder $isProductPreorder
     * @param Salable $salable
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        \Webkul\OptionsWithStockAndImages\Helper\Data   $helper,
        \Webkul\OptionsWithStockAndImages\Logger\Logger $logger,
        GetOrderItemCombo                               $getOrderItemCombo,
        IsProductPreorder                               $isProductPreorder,
        Salable                                         $salable,
        ResourceConnection                              $resourceConnection
    )
    {
        $this->isProductPreorder = $isProductPreorder;
        $this->helper = $helper;
        $this->logger = $logger;
        $this->salable = $salable;
        $this->resource = $resourceConnection;
        $this->getSaleOrderItemCombo = $getOrderItemCombo;
    }

    /**
     * @param \Webkul\OptionsWithStockAndImages\Observer\CheckoutSubmitAllAfter $subject
     * @param \Closure $proceed
     * @param $order
     * @return void
     * @throws \Exception
     */
    public function aroundUpdateData(
        \Webkul\OptionsWithStockAndImages\Observer\CheckoutSubmitAllAfter $subject,
        \Closure                                                          $proceed,
                                                                          $order
    )
    {
        try {
            $this->salable->getConnection()->beginTransaction();
            $subtractStockQty = [];
            $messages = [];
            foreach ($order->getAllVisibleItems() as $item) {
                $product = $item->getProduct();
                if (!$product
                    || IsVirtualProduct::check($product)
                    || empty($product->getOptions())
                    || $this->isProductPreorder->execute((int)$product->getId())
                ) {
                    continue;
                }
                $comb = $this->getSaleOrderItemCombo->get($item);
                if (empty($comb)) {
                    $this->logger->critical(__('Could not get combo For Order Item ID from options:%1,ProductId: %2,Combo: %3,', $item->getId(), $comb));
                    $this->logger->critical(__('Replace By $Item->getCombo():%1', $item->getCombo()));
                    $comb = $item->getCombo();
                }
                $extensions = $product->getExtensionAttributes();
                $normalizeMap = ($extensions && method_exists($extensions, 'getNormalizeVariationMap'))
                    ? ($extensions->getNormalizeVariationMap() ?? [])
                    : [];
                $comb = NormalizeCombo::execute($comb, $normalizeMap);
                $variation = $this->helper->getCombData((int)$product->getRowId(), $comb);
                if (empty($variation->getId())) {
                    $this->logger->critical(__('No combo found for ProductId:%1,Combo:2,', $product->getRowId(), $comb));
                    continue;
                }
                $option_type_id = [];
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
                    $options = $item->getProductOptions();
                    $msgCO = [];
                    if (!empty($options['options'])) {
                        foreach ($options['options'] as $option) {
                            $optDataArr = $optionData[$option['option_id']];
                            if (isset($optDataArr[$option['option_value']])) {
                                $comb .= $optDataArr[$option['option_value']] . "_";
                                $option_type_id[] = $option['option_value'];
                                $msgCO[] = $option['label'] . ' : ' . $option['value'];
                            }
                        }
                    }
                    if (count($msgCO)) {
                        $msg[] = __('Custom option: %1', implode(' , ', $msgCO));
                    }
                    $price = $variation->getPrice();
                    $cost = $variation->getCost();
                    $this->logger->info('CheckoutSubmitAllAfter');
                    $this->logger->info(json_encode([
                        'comb' => $comb,
                        'stock' => $variation->getStock() + $item->getQtyOrdered()
                    ]));
                    if ($variation->getIsSync()) {
                        if (array_key_exists($variation->getSku(), $subtractStockQty)) {
                            $subtractStockQty[$variation->getSku()] = (int)$subtractStockQty[$variation->getSku()] + $item->getQtyOrdered();
                        } else {
                            $subtractStockQty[$variation->getSku()] = $item->getQtyOrdered();
                        }
                        $msg[] = __('Variation: %1', $comb);
                        $messages[$variation->getSku()] = implode(' - ', $msg);
                    } else {
                        $qty = $variation->getStock() - $item->getQtyOrdered();
                        if ($qty < 0) {
                            $qty = 0;
                        }
                        $variation->setStock($qty);
                    }
                    if (!empty($variation->getSku())) {
                        $variationSku = $variation->getSku();
                        $variation->setIsLockSku(1);
                        if (!empty($variationSku)) {
                            $item->setVariationSku($variationSku);
                        }
                    }

                    if (!empty($cost) && $cost > 0) {
                        $item->setBaseCost($cost);
                        $item->setOriginCost($cost);
                    }
                    if (!empty($price) && $price > 0) {
                        $item->setVariationPrice($price);
                        $item->setOriginalPrice($price);
                        $item->setBaseOriginalPrice($price);
                    }
                    $variation->setReadyToShipQty(
                        $variation->getReadyToShipQty() + $item->getQtyOrdered()
                    )->save();
                    // Extract shared values to ensure consistency between ORM save and direct SQL update
                    $specTitle = $variation->getComb();
                    $specialPriceValue = $item->getPriceInclTax();

                    $item->setSpecTitle($specTitle);
                    $item->setSpecialPrice($specialPriceValue);
                    if (count($option_type_id)) {
                        $customOptionSkus = $this->getCustomOptionSkus($option_type_id);
                        if (count($customOptionSkus)) {
                            $item->setOptionSku(implode('-', $customOptionSkus));
                        }
                        $this->saveCustomOptionIsBought($option_type_id);
                    }
                    $item->save();

                    // Force direct SQL update to bypass OrderRepository cached save() overwrite
                    $connection = $this->salable->getConnection();
                    $connection->update(
                        $connection->getTableName('sales_order_item'),
                        [
                            'spec_title'      => $specTitle,
                            'special_price'   => $specialPriceValue,
                            'variation_price' => $price,
                            'option_sku'      => $item->getOptionSku(),
                        ],
                        ['item_id = ?' => $item->getId()]
                    );
                }
                $this->salable->syncNeedToRefill([$product->getId()]);
            }
            $this->subtractStockQty($subtractStockQty, $order, $messages);
            $this->salable->getConnection()->commit();
        } catch (\Exception $e) {
            try {
                $this->salable->getConnection()->rollBack();
            } catch (\Exception $e2) {
                // Ignore rollback error if no transaction active
            }
            $this->logger->info($e->getMessage());
            throw $e;
        }
    }

    /**
     * @param $skus
     * @param $order
     * @param $messages
     * @return void
     */
    private function subtractStockQty($skus, $order, $messages)
    {
        if (count($skus)) {
            foreach ($skus as $sku => $qty) {
                $message = __('Product ordered (order: %1)', $order->getIncrementId());
                if (isset($messages[$sku])) {
                    $message = __('Product ordered (%1)', $messages[$sku]);
                }
                $this->salable->subtractStockQty($sku, $qty, $order, $message);
            }
        }
    }

    /**
     * @param $option_type_id
     * @return array
     */
    private function getCustomOptionSkus($option_type_id)
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('catalog_product_option_type_value');

        $select = $connection->select()
            ->from($table, ['sku'])
            ->where('option_type_id IN (?)', $option_type_id)
            ->where('sku IS NOT NULL');
        return $connection->fetchCol($select);
    }

    /**
     * @param $option_type_id
     * @return void
     * @throws \Exception
     */
    private function saveCustomOptionIsBought($option_type_id)
    {
        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('catalog_product_option_type_value');

        $connection->beginTransaction();
        try {
            $updateData = [
                'is_bought' => 1
            ];
            $whereUpdate = [
                'option_type_id IN (?)' => $option_type_id
            ];
            $connection->update(
                $table,
                $updateData,
                $whereUpdate
            );
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }
}
