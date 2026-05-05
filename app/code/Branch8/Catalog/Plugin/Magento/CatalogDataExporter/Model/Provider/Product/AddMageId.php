<?php

namespace Branch8\Catalog\Plugin\Magento\CatalogDataExporter\Model\Provider\Product;

use Magento\CatalogDataExporter\Model\Provider\Product\Attributes;
use Magento\Framework\App\ResourceConnection;

class AddMageId
{
    private ResourceConnection $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    )
    {
        $this->resourceConnection = $resourceConnection;
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
        $attributeCode = 'mage_id';

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
            $additionalAttributes[$newKey] = [
                'productId' => $productId,
                'storeViewCode' => $storeViewCode,
                'attributes' => [
                    'attributeCode' => $attributeCode,
                    'value' => [
                        $product['productId']
                    ]
                ]
            ];

        }

        return array_merge($result, $additionalAttributes);
    }
}
