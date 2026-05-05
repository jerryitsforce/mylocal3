<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\OptionsWithStockAndImages\Plugin\Webkul\OptionsWithStockAndImages\Observer;

use Branch8\OptionsWithStockAndImages\Model\Actions\GetQuoteItemCombo;
use Branch8\OptionsWithStockAndImages\Model\Actions\GetStockCombo;
use Branch8\OptionsWithStockAndImages\Model\Actions\NormalizeCombo;
use Branch8\OptionsWithStockAndImages\Model\IsPreorderItem;
use Branch8\OptionsWithStockAndImages\Model\Actions\IsVirtualProduct;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;

class CheckoutSubmitBeforeObserver
{
    /**
     * @var \Branch8\OptionsWithStockAndImages\Helper\Salable
     */
    protected $customOptionSalableHelper;
    private IsPreorderItem $isPreorderItem;
    private GetStockCombo $getStockCombo;
    private GetQuoteItemCombo $getQuoteItemCombo;

    private $stockCache = [];

    /**
     * @param \Branch8\OptionsWithStockAndImages\Helper\Salable $customOptionSalableHelper
     * @param GetQuoteItemCombo $getQuoteItemCombo
     * @param GetStockCombo $getStockCombo
     * @param IsPreorderItem $isPreorderItem
     */
    public function __construct(
        \Branch8\OptionsWithStockAndImages\Helper\Salable $customOptionSalableHelper,
        GetQuoteItemCombo                                 $getQuoteItemCombo,
        GetStockCombo                                     $getStockCombo,
        IsPreorderItem                                    $isPreorderItem
    )
    {
        $this->isPreorderItem = $isPreorderItem;
        $this->getStockCombo = $getStockCombo;
        $this->getQuoteItemCombo = $getQuoteItemCombo;
        $this->customOptionSalableHelper = $customOptionSalableHelper;
    }

    /**
     * @throws LocalizedException
     */
    public function aroundExecute(
        \Webkul\OptionsWithStockAndImages\Observer\CheckoutSubmitBeforeObserver $subject,
        \Closure                                                                $proceed,
        \Magento\Framework\Event\Observer                                       $observer
    )
    {
        $connection = $this->customOptionSalableHelper->getConnection();
        $connection->beginTransaction();
        try {
            $cart = $observer->getEvent()->getData('quote');
            $notAvailableArr = [];
            /**
             * @var $item \Magento\Quote\Model\Quote\Item
             * @var $product \Magento\Catalog\Model\Product
             * @var $extensions \Magento\Catalog\Api\Data\ProductExtension
             */
            foreach ($cart->getAllItems() as $item) {
                if ((bool)$item->getData('available_to_checkout') === false
                    || !($product = $item->getProduct())
                    || empty($product->getOptions())
                    || IsVirtualProduct::check($product)
                    || (($isPreorderItem = $this->isPreorderItem->execute($item)) === true && ($this->getStockPreorder($item) <= 0))
                    || empty(trim($combo = trim($this->getQuoteItemCombo->get($item))))
                ) {
                    continue;
                }
                if ($isPreorderItem && $this->getStockPreorder($item) > 0) {
                    $notAvailableArr[] = $combo ? sprintf("%s (%s) ", $item->getName(), $combo) : $item->getName();
                    continue;
                }
                $extensions = $product->getExtensionAttributes();
                $productVariants = $extensions->getVariations();
                $productRowId = $item->getProduct()->getRowId();
                $normalizeMap = $extensions->getNormalizeVariationMap() ?? [];
                $combo = NormalizeCombo::execute($combo, $normalizeMap);
                $isExistVariant = isset($productVariants[$combo]);
                $requestQty = $item->getQty();
                if (!$isExistVariant || !$this->checkStockAvailabilityForCombo($requestQty, $productRowId, $combo)) {
                    $notAvailableArr[] = $item->getName() . " (" . trim($combo, '_') . ")";
                }
            }
            if (!empty($notAvailableArr)) {
                $notAvailable = implode(", ", array_unique($notAvailableArr));
                throw new LocalizedException(
                    __(
                        'We do not have %1 as many as you are trying to order.',
                        $notAvailable
                    )
                );
            }
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            $subject->logger->info($e->getMessage());
            throw $e;
        }
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $item
     * @return int|null
     * @throws LocalizedException
     */
    private function getStockPreorder(\Magento\Quote\Model\Quote\Item $item)
    {
        $product = $item->getProduct();
        if (empty($product)) {
            throw new LocalizedException(__('No product found'));
        }
        if (isset($this->stockCache[$product->getId()])) {
            return $this->stockCache[$product->getId()];
        }
        $combo = $this->getQuoteItemCombo->get($item);
        $this->stockCache[$product->getEntityId()] = $this->getStockCombo->execute((int)$product->getRowId(), $combo);
        return $this->stockCache[$product->getEntityId()];
    }

    /**
     * @param $requestQty
     * @param $productRowId
     * @param $combo
     * @return bool
     * @throws \Exception
     */
    private function checkStockAvailabilityForCombo($requestQty, $productRowId, $combo)
    {
        return $this->customOptionSalableHelper->checkStockAvailabilityForCombo($requestQty, (int)$productRowId, $combo);
    }
}
