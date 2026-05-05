<?php
namespace Branch8\OptionsWithStockAndImages\Model;

use Branch8\OptionsWithStockAndImages\Api\PriceManagementInterface;
use Branch8\OptionsWithStockAndImages\Model\Resolver\IndexedVariationsPriceResolver;

class PriceManagement implements PriceManagementInterface
{
    private $priceResolver;

    public function __construct(IndexedVariationsPriceResolver $priceResolver)
    {
        $this->priceResolver = $priceResolver;
    }

    public function getFinalPriceByComb(int $productId, string $variationComb)
    {
        return $this->priceResolver->resolve($productId, $variationComb);
    }

    /**
     * @param int $productId
     * @return mixed
     */
    public function getFinalPrice(int $productId)
    {
        return $this->priceResolver->getFinalPrice($productId);
    }
}
