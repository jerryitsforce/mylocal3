<?php

namespace Branch8\ProductKeywords\Observer;

use Magento\Framework\Event\ObserverInterface;

class CatalogProductSaveAfter implements ObserverInterface
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
        $product = $observer->getEvent()->getDataObject();
        $productKeyword = $product->getData('search_tag');
        if ($productKeyword) {
            $connection = $this->_resource->getConnection();
            $tableKw = $this->_resource->getTableName('catalog_keyword');
            $table = $this->_resource->getTableName('catalog_keyword_product');
            $arrayKeyword = array_map('trim', explode(',', $productKeyword));
            $where = ['product_id IN (?)' => [$product->getRowId()]];
            $connection->delete($table, $where);

            $data = [];
            foreach ($arrayKeyword as $keyword) {
                $keywordId = $connection->fetchOne('SELECT entity_id FROM ' . $tableKw . ' WHERE keyword = ?', $keyword);
                if (!$keywordId) {
                    $connection->insert($tableKw, ['keyword' => $keyword]);
                    $keywordId = $connection->lastInsertId($tableKw);
                }
                $data[] = ['keyword_id' => (int)$keywordId, 'product_id' => (int)$product->getRowId()];
            }

            if (count($data) > 0)
                $connection->insertMultiple($table, $data);
        }
    }
}
