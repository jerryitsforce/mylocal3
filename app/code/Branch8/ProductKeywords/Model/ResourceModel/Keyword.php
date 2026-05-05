<?php
namespace Branch8\ProductKeywords\Model\ResourceModel;

/**
 * Class Keyword
 * @package Branch8\Keyword\Model\ResourceModel
 */
class Keyword extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct()
    {
        $this->_init(
            'catalog_keyword',
            'entity_id'
        );
    }

    /**
     * Perform operations before object save
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _beforeSave(\Magento\Framework\Model\AbstractModel $object)
    {
        if (!$this->getIsUniqueKeywordName($object)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Keyword already exists.'));
        }
        return $this;
    }

    /**
     * Perform operations after object save
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        $table = $this->getTable('catalog_keyword_product');
        $products = (array)$object->getProducts();
        if ($products || isset($_POST['products'])) {
            $where = ['keyword_id = ?' => (int)$object->getId()];
            $this->getConnection()->delete($table, $where);
        }

        $data = [];
        foreach ($products as $product) {
            $data[] = ['keyword_id' => (int)$object->getId(), 'product_id' => (int)$product];
        }

        if ($data)
            $this->getConnection()->insertMultiple($table, $data);
        return parent::_afterSave($object);
    }

    /**
     * Check for unique of designer .
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return bool
     */
    public function getIsUniqueKeywordName(\Magento\Framework\Model\AbstractModel $object)
    {
        $select = $this->getConnection()->select()
            ->from(array('d' => $this->getMainTable()))
            ->where('d.keyword = ?', $object->getData('keyword'));

        if ($object->getKeywordId()) {
            $select->where('d.entity_id <> ?', $object->getEntityId());
        }

        if ($this->getConnection()->fetchRow($select)) {
            return false;
        }

        return true;
    }

    public function getKeywordProduct($keywordId = null)
    {
        $select = $this->getConnection()->select()
            ->from(['dp' => $this->getTable('catalog_keyword_product')]);
        if ((int)$keywordId) {
            $select->where('keyword_id = ?', $keywordId);
        }
        return $this->getConnection()->fetchAll($select);
    }

    public function getProductIdGrid($keywordId = null)
    {
        $select = $this->getConnection()->select()
            ->from(['dp' => $this->getTable('catalog_keyword_product')], 'product_id');
        if ((int)$keywordId) {
            $select->where('keyword_id <> ?', $keywordId);
        }
        return $this->getConnection()->fetchCol($select);
    }
}
