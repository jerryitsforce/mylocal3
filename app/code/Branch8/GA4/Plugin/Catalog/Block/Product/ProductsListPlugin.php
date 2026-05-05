<?php
namespace Branch8\GA4\Plugin\Catalog\Block\Product;

use Magento\CatalogWidget\Block\Product\ProductsList;

class ProductsListPlugin
{
    /**
     * @param ProductsList $subject
     * @param array $result
     * @return array
     */
    public function afterGetCacheKeyInfo(ProductsList $subject, array $result): array
    {
        if($subject->getData('promotion_id')) {
            $result[] = $subject->getData('promotion_id');
        }

        if ($subject->getData('promotion_name')) {
            $result[] = $subject->getData('promotion_name');
        }

        if ($subject->getData('item_list_id')) {
            $result[] = $subject->getData('item_list_id');
        }

        if ($subject->getData('item_list_name')) {
            $result[] = $subject->getData('item_list_name');
        }

        return $result;
    }

}
