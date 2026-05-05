<?php

namespace Branch8\MarketplaceProduct\Plugin\Product;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;

class Grid
{
    public function afterAAddAttributeToSelect(ProductCollection $subject, $result)
    {
        $subject->addAttributeToSelect('admin_user_updated');
        return $result;
    }
}
