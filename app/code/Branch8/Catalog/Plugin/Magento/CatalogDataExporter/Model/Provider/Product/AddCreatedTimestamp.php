<?php

namespace Branch8\Catalog\Plugin\Magento\CatalogDataExporter\Model\Provider\Product;

use Magento\CatalogDataExporter\Model\Provider\Product\Attributes;
use Magento\Framework\App\ResourceConnection;

class AddCreatedTimestamp
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
        $attributeCode = 'created_at_timestamp';

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
            $timeStamp = $this->getTimeStamp($productId);
            $additionalAttributes[$newKey] = [
                'productId' => $productId,
                'storeViewCode' => $storeViewCode,
                'attributes' => [
                    'attributeCode' => $attributeCode,
                    'value' => [
                        $timeStamp
                    ]
                ]
            ];

        }

        return array_merge($result, $additionalAttributes);
    }

    /**
     * @param $productId
     * @return int
     */
    private function getTimeStamp($productId)
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select();
        $select->from('catalog_product_entity', [
            'created_at_timestamp' => new \Zend_Db_Expr("UNIX_TIMESTAMP(CONVERT_TZ(created_at, @@session.time_zone, '+00:00'))")
        ])->where('entity_id = ?', $productId);
        return (int)$connection->fetchOne($select);
    }
}
