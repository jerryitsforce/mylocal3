<?php

namespace Branch8\Yoxi\Model\ResourceModel;

use Branch8\Yoxi\Model\YoxiBatchSetting as YoxiBatchSettingModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class YoxiBatchSetting extends AbstractDb
{
    protected function _construct()
    {
        $this->_init(YoxiBatchSettingModel::TABLE_NAME, YoxiBatchSettingModel::ID_FIELD_NAME);
    }
}
