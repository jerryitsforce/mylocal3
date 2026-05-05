<?php
namespace Branch8\ProductKeywords\Model;

/**
 * Class Keyword
 * @package Branch8\Keyword\Model
 */
class Keyword extends \Magento\Framework\Model\AbstractModel
{
    protected $_eventPrefix = 'b8_keyword';
    /**
     * Initialize Keyword model
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('Branch8\ProductKeywords\Model\ResourceModel\Keyword');
    }

    public function getKeywordsByProduct(\Magento\Catalog\Model\Product $product)
    {
        $collection = $this->getCollection()
            ->addFieldToSelect('*')
            ->addProductFilter($product)
            ->setOrder('name', 'ASC');
        return $collection;
    }

    public function getKeywordByProductId($productId)
    {
        $collection = $this->getCollection()->join(['dp' => 'catalog_keyword_product'], 'main_table.entity_id = dp.keyword_id', [])
            ->addFieldToFilter('dp.product_id', $productId)->getFirstItem();
        return $collection;
    }

    public function getKeywordProductIds()
    {
        if ($this->hasData('keyword_product_id'))
            return $this->_getData('keyword_product_id');
        if ($this->getId() && !$this->hasData('keyword_product_id')) {
            $products = $this->_getResource()->getKeywordProduct($this->getId());
            $productIds = [];
            foreach ($products as $prod) {
                $productIds[] = $prod['product_id'];
            }
            $this->setData('keyword_product_id', $productIds);
            return $productIds;
        }
        return [];
    }
}
