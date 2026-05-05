<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Branch8\MarketplaceProductImport\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\ImportExport\Model\Import;

/**
 * Import entity of grouped product type
 */
class FormatGroupedProduct
{
    /**
     * Default delimiter for sku and qty.
     */
    public const SKU_QTY_DELIMITER = '=';

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connection;

    /**
     * @param ResourceConnection $resource
     */
    public function __construct(
        ResourceConnection $resource
    ) {
        $this->_resource = $resource;
        $this->connection = $resource->getConnection();
    }

    /**
     * Parse the row data.
     *
     * @param array $wholeData
     * @param array $rowData
     *
     * @return array
     */
    public function execute($wholeData, $rowData)
    {
        if (empty($rowData['associated_skus'])) {
            $wholeData['product']['type_id'] = 'simple';
            return $wholeData;
        } else {
            $wholeData['product']['type_id'] = 'grouped';
        }
        $associatedSkusQty = $rowData['associated_skus'];
        $associatedSkusAndQtyPairs = explode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $associatedSkusQty);
        $position = 0;
        $linked = [];
        $listSku = [];
        foreach ($associatedSkusAndQtyPairs as $associatedSkuAndQty) {
            ++$position;
            $associatedSkuAndQty = explode(self::SKU_QTY_DELIMITER, $associatedSkuAndQty);
            $associatedSku = isset($associatedSkuAndQty[0]) ? strtolower(trim($associatedSkuAndQty[0])) : null;
            $qty = empty($associatedSkuAndQty[1]) ? 0 : trim($associatedSkuAndQty[1]);
            $linked[$associatedSku]['id'] = $position;
            $linked[$associatedSku]['position'] = $position;
            $linked[$associatedSku]['qty'] = $qty;
            $listSku[] = $associatedSku;
        }
        if (!empty($listSku)) {
            $skuToProducts = $this->retrieveProductsByCachedSkus($listSku);
            foreach ($linked as $sku => $linkData) {
                if (isset($skuToProducts[$sku])) {
                    $wholeData['links']['associated'][$skuToProducts[$sku]] = $linkData;
                    $wholeData['links']['associated'][$skuToProducts[$sku]]['id'] = $skuToProducts[$sku];
                }
            }
        }

        return $wholeData;
    }

    /**
     * Retrieve mapping between skus and products.
     *
     */
    protected function retrieveProductsByCachedSkus($listSku): array
    {
        return $this->connection->fetchPairs(
            $this->connection->select()->from(
                $this->_resource->getTableName('catalog_product_entity'),
                ['sku', 'entity_id']
            )->where(
                'sku IN (?)',
                $listSku
            )
        );
    }
}
