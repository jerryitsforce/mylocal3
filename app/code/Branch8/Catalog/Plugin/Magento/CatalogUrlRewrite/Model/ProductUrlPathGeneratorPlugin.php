<?php
namespace Branch8\Catalog\Plugin\Magento\CatalogUrlRewrite\Model;

use Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator;
use Magento\Catalog\Model\Product;

class ProductUrlPathGeneratorPlugin
{
    /**
     * @param ProductUrlPathGenerator $subject
     * @param callable $proceed
     * @param Product $product
     * @return string
     */
    public function aroundGetUrlKey(ProductUrlPathGenerator $subject, callable $proceed, $product): string
    {
        $urlKey = (string)$product->getUrlKey();
        $urlKey = trim(strtolower($urlKey));
        $replace = $product->getSku();

        //Not generate SKU for product yet
        if(!$product->getSku()) {
            $replace = uniqid();
        }

        return $product->formatUrlKey($urlKey ?: $replace);
    }
}
