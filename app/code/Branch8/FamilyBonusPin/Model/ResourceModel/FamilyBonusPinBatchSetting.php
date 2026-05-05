<?php

namespace Branch8\FamilyBonusPin\Model\ResourceModel;

use Branch8\FamilyBonusPin\Model\FamilyBonusPinBatchSetting as FamilyBonusPinBatchSettingModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class FamilyBonusPinBatchSetting extends AbstractDb
{
    protected function _construct()
    {
        $this->_init(FamilyBonusPinBatchSettingModel::TABLE_NAME, FamilyBonusPinBatchSettingModel::ID_FIELD_NAME);
    }
}
