<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Plugin\Product;

use Branch8\MarketplaceProduct\Model\ResourceModel\MarkPriceChange;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Framework\Model\AbstractModel;

class UpdateQuoteAfterPriceChange
{
    /**
     * @var MarkPriceChange
     */
    private MarkPriceChange $markPriceChange;

    /**
     * UpdateQuoteAfterPriceChange constructor.
     *
     * @param MarkPriceChange $markPriceChange
     */
    public function __construct(MarkPriceChange $markPriceChange)
    {
        $this->markPriceChange = $markPriceChange;
    }

    /**
     * Update quote after product price change.
     *
     * @param Product $subject
     * @param Product $result
     * @param AbstractModel $product
     *
     * @return Product
     */
    public function afterSave(Product $subject, Product $result, AbstractModel $product): Product
    {
        $price = $product->getData('price');
        $originalPrice = $product->getOrigData('price');
        $tierPriceChanged = $product->getData('tier_price_changed');
        if ((!empty($originalPrice) && ($originalPrice != $price)) || $tierPriceChanged) {
            $this->markPriceChange->execute([$product->getId()]);
        }
        return $result;
    }
}
