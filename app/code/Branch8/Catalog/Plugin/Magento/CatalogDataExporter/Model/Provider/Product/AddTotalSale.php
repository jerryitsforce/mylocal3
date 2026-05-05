<?php

namespace Branch8\Catalog\Plugin\Magento\CatalogDataExporter\Model\Provider\Product;

use Branch8\Catalog\Model\Actions\GetTotalSale;
use Magento\CatalogDataExporter\Model\Provider\Product\Attributes;

class AddTotalSale
{
    private GetTotalSale $getTotalSale;

    /**
     * @param GetTotalSale $getTotalSale
     */
    public function __construct(
        GetTotalSale $getTotalSale
    )
    {
        $this->getTotalSale = $getTotalSale;
    }

    /**
     * @param Attributes $subject
     * @param array $result
     * @param $arguments
     * @return array
     * @throws \Zend_Db_Statement_Exception
     */
    public function afterGet(Attributes $subject, array $result, $arguments): array
    {
        $additionalAttributes = [];
        $attributeCode = 'total_sale';

        foreach ($result as $product) {
            if (!isset($product['productId']) || !isset($product['storeViewCode'])) {
                continue;
            }
            $productId = $product['productId'];
            $storeViewCode = $product['storeViewCode'];
            $newKey = \implode('-', [$product['storeViewCode'], $product['productId'], $attributeCode]);
            if (isset($additionalAttributes[$newKey])) {
                continue;
            }
            $totalSale = $this->getTotalSale->get($productId);
            $additionalAttributes[$newKey] = [
                'productId' => $productId,
                'storeViewCode' => $storeViewCode,
                'attributes' => [
                    'attributeCode' => $attributeCode,
                    'value' => [
                        $totalSale
                    ]
                ]
            ];

        }

        return array_merge($result, $additionalAttributes);
    }
}
