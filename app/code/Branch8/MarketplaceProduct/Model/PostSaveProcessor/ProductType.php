<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       09/02/2026
 */

namespace Branch8\MarketplaceProduct\Model\PostSaveProcessor;

use Magento\Framework\App\RequestInterface;

class ProductType implements PostSaveProcessorInterface
{
    /**
     * @param RequestInterface $request
     * @param $productId
     * @param $wholeData
     * @return array|mixed
     */
    public function process(RequestInterface $request, $productId, &$wholeData = [])
    {
        if (isset($wholeData['type'])
            && $wholeData['type'] == 'bundle') {
            if ($productId == 0 && !array_key_exists('price_type', $wholeData['product'])) {
                $wholeData['product']['price_type'] = 1;
            }
            if (!array_key_exists('sku_type', $wholeData['product'])) {
                $wholeData['product']['sku_type'] = 1;
            }
            if (!array_key_exists('weight_type', $wholeData['product'])) {
                $wholeData['product']['weight_type'] = 1;
            }
            if (!array_key_exists('price_view', $wholeData['product'])) {
                $wholeData['product']['price_view'] = 0;
            }
        }
        return $wholeData;
    }
}
