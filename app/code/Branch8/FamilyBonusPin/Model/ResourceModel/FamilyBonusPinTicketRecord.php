<?php

namespace Branch8\FamilyBonusPin\Model\ResourceModel;

use Branch8\FamilyBonusPin\Model\FamilyBonusPinTicketRecord as FamilyBonusPinTicketRecordModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class FamilyBonusPinTicketRecord extends AbstractDb
{
    protected function _construct()
    {
        $this->_init(FamilyBonusPinTicketRecordModel::TABLE_NAME, FamilyBonusPinTicketRecordModel::ID_FIELD_NAME);
    }
}
