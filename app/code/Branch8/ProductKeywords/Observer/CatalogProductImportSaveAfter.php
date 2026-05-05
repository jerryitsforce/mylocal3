<?php

namespace Branch8\ProductKeywords\Observer;

use Magento\Framework\Event\ObserverInterface;

class CatalogProductImportSaveAfter implements ObserverInterface
{
    protected $_resource;

    /**
     * Constructor.
     * @param \Magento\Framework\App\ResourceConnection $resource
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->_resource = $resource;
    }

    /**\
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(
        \Magento\Framework\Event\Observer $observer
    ) {
        $bunch = $observer->getBunch();
        $adapter = $observer->getEvent()->getAdapter();
        $tableKw = $this->_resource->getTableName('catalog_keyword');
        $attributeKeyword = $this->_resource->getConnection()->fetchAll('SELECT * FROM ' . $tableKw);
        $existingKeywords = array_column($attributeKeyword, 'keyword');
        $connection = $this->_resource->getConnection();

        foreach($bunch as $product) {
            $productKeyword = $product['search_tag'];
            $productId = $adapter->getNewSku($product['sku'])['row_id'];
            if ($productKeyword) {
                $table = $this->_resource->getTableName('catalog_keyword_product');
                $arrayKeyword = array_map('trim', explode(',', $productKeyword));
                $where = ['product_id IN (?)' => [$productId]];
                $connection->delete($table, $where);

                $data = [];
                foreach ($arrayKeyword as $keywordLabel) {
                    $key = array_search(strtolower($keywordLabel), array_map('strtolower', $existingKeywords));
                    if ($key !== false) {
                        $keywordId = $attributeKeyword[$key]['entity_id'];
                        $data[] = ['keyword_id' => (int)$keywordId, 'product_id' => (int)$productId];
                    } else {
                        $connection->insert($tableKw, ['keyword' => $keywordLabel], ['keyword']);
                        $keywordId = $connection->lastInsertId($tableKw);
                        $existingKeywords[$keywordId] = $keywordLabel;
                        $attributeKeyword[$keywordId] = ['entity_id' => $keywordId, 'keyword' => $keywordLabel];
                        $data[] = ['keyword_id' => (int)$keywordId, 'product_id' => (int)$productId];
                    }
                }

                if (count($data) > 0)
                    $this->_resource->getConnection()->insertMultiple($table, $data);
            }
        }
    }
}
