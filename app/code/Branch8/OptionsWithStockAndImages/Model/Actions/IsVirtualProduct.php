<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       26/03/2026
 */

namespace Branch8\OptionsWithStockAndImages\Model\Actions;

use Branch8\HotaiCore\Model\Product\VirtualProductType;
use Magento\Catalog\Model\Product;

class IsVirtualProduct
{
    /**
     * @param Product $product
     * @return bool
     */
    public static function check(Product $product): bool
    {
        return in_array($product->getData(VirtualProductType::ATTRIBUTE_CODE), VirtualProductType::TYPES_TICKET);
    }
}
