<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model\ResourceModel;
/**
 * Category HelpDesk Model
 */
class Category extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('branch8_helpdesk_category', 'category_id');
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this|Category
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        parent::_afterSave($object);
        $categoryId = 'category_id';
        $oldStores = $this->lookupStoreIds((int)$object->getId());
        $newStores = (array)$object->getStores();
        if (empty($newStores)) {
            $newStores = (array)$object->getStoreId();
        }
        $table = $this->getTable('branch8_helpdesk_category_store');
        $delete = array_diff($oldStores, $newStores);
        if ($delete) {
            $where = [
                $categoryId . ' = ?' => (int)$object->getData($categoryId),
                'store_id IN (?)' => $delete,
            ];
            $this->getConnection()->delete($table, $where);
        }

        $insert = array_diff($newStores, $oldStores);
        if ($insert) {
            $data = [];
            foreach ($insert as $storeId) {
                $data[] = [
                    $categoryId => (int)$object->getData($categoryId),
                    'store_id' => (int)$storeId
                ];
            }
            $this->getConnection()->insertMultiple($table, $data);
        }

        return $this;
    }

    /**
     * Get store ids to which specified item is assigned
     *
     * @param int $storeIds
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function lookupStoreIds($storeIds)
    {
        $connection = $this->getConnection();
        $linkField = 'category_id';
        $select = $connection->select()
            ->from(['bhcs' => $this->getTable('branch8_helpdesk_category_store')], 'store_id')
            ->where('bhcs.' . $linkField . ' = :category_id');

        return $connection->fetchCol($select, ['category_id' => (int)$storeIds]);
    }

}
