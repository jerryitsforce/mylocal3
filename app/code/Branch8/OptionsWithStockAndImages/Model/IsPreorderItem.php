<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       10/02/2026
 */

namespace Branch8\OptionsWithStockAndImages\Model;

use Branch8\Preorder\Model\IsPreorderProduct;

class IsPreorderItem
{
    private IsPreorderProduct $isPreorderProduct;

    /**
     * @param IsPreorderProduct $isPreorderProduct
     */
    public function __construct(
        IsPreorderProduct $isPreorderProduct
    )
    {
        $this->isPreorderProduct = $isPreorderProduct;
    }

    /**
     * @param \Magento\Quote\Model\Quote\Item $cartItem
     * @return bool
     */
    public function execute(\Magento\Quote\Model\Quote\Item $cartItem)
    {
        $product = $cartItem->getProduct();
        if ($product && $product->getId()) {
            return $this->isPreorderProduct->checkIsPreorder((int)$product->getId());
        }
        return false;
    }
}
