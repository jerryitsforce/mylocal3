<?php

declare(strict_types=1);

namespace Branch8\Catalog\Model\ResourceModel;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ProductChangeHistory extends AbstractDb
{
    /**
     * @inheritDoc
     */
    protected function _construct(): void
    {
        $this->_init('branch8_product_change_history', 'history_id');
    }

    /**
     * Inserts a table row with specified data.
     *
     * @param array $data
     *
     * @return void
     *
     * @throws LocalizedException
     */
    public function insert(array $data): void
    {
        $this->getConnection()->insert($this->getMainTable(), $data);
    }

    /**
     * Get a table row by history ID.
     *
     * @param int $historyId
     *
     * @return array|null
     *
     * @throws LocalizedException
     */
    public function getById(int $historyId): ?array
    {
        $select = $this->getConnection()->select()
            ->from($this->getMainTable())
            ->where('history_id = ?', $historyId);

        return $this->getConnection()->fetchRow($select);
    }
}
