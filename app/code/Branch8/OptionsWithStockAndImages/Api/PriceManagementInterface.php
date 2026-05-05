<?php
namespace Branch8\OptionsWithStockAndImages\Api;

interface PriceManagementInterface
{
    /**
     * @param int $productId
     * @param string $variationComb
     * @return float|null
     */
    public function getFinalPriceByComb(int $productId, string $variationComb);

    /**
     * @param int $productId
     * @return array
     */
    public function getFinalPrice(int $productId);
}
