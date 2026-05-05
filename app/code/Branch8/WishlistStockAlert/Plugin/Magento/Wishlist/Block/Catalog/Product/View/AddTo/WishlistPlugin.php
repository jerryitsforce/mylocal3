<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       05/04/2026
 */

namespace Branch8\WishlistStockAlert\Plugin\Magento\Wishlist\Block\Catalog\Product\View\AddTo;

use Branch8\WishlistStockAlert\Model\Actions\IsVariantProduct;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;

class WishlistPlugin
{
    /**
     * @param IsVariantProduct $isVariantProduct
     * @param Registry $registry
     */
    public function __construct(readonly IsVariantProduct $isVariantProduct, readonly Registry $registry)
    {

    }

    /**
     * @param Template $subject
     * @param $result
     * @return array
     */
    public function afterGetWishlistOptions(Template $subject, $result)
    {
        $currentProduct = $this->registry->registry('current_product');
        $isVariantProduct = false;
        if ($currentProduct) {
            $isVariantProduct = $this->isVariantProduct->execute($currentProduct);
        }
        return array_merge($result, ['isVariantProduct' => $isVariantProduct]);
    }
}
