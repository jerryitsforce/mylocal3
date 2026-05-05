<?php
namespace Branch8\ProductKeywords\Model\ResourceModel\Keyword;

use Magento\Catalog\Model\Product;

/**
 * Class Collection
 * @package Branch8\Testmodule\Model\ResourceModel\Vendor
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * @var array
     */
    protected $_joinedFields = [];

    protected function _construct()
    {
        $this->_init(
            'Branch8\ProductKeywords\Model\Keyword',
            'Branch8\ProductKeywords\Model\ResourceModel\Keyword'
        );
    }

    /**
     * Filer by product's id
     *
     * @param $product
     * @return $this
     */
    public function addProductFilter($product = null)
    {
        if ($product) {
            if ($product instanceof Product) {
                $product = $product->getId();
            }
            if (!isset($this->_joinedFields['product'])) {
                $this->getSelect()->join(
                    ['b8_keyword2product' => $this->getTable('catalog_keyword_product')],
                    'b8_keyword2product.keyword_id = main_table.entity_id'
                );
                $this->getSelect()->where('b8_keyword2product.product_id = ?', $product);
                $this->_joinedFields['product'] = true;
            }
        } else {
            if (!isset($this->_joinedFields['product'])) {
                $this->getSelect()->join(
                    ['b8_keyword2product' => $this->getTable('catalog_keyword_product')],
                    'b8_keyword2product.keyword_id = main_table.entity_id'
                )->group('main_table.entity_id');
                $this->_joinedFields['product'] = true;
            }
        }
        return $this;
    }

    public function addProductCount()
    {
        $this->getSelect()
            ->joinLeft(
                ['pcount' => $this->getTable('catalog_keyword_product')],
                'main_table.entity_id = pcount.keyword_id',
                ['count_product' => 'count(pcount.product_id)']
            )->group('main_table.entity_id');

        return $this;
    }
}
