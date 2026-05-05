<?php

namespace HotaiConnected\Qware\Model\ResourceModel;

use HotaiConnected\Qware\Model\QwareTicketRecord as QwareTicketRecordModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class QwareTicketRecord extends AbstractDb
{
    // 狀態常數
    const STATUS_RETURNED = -1; // 已退貨
    const STATUS_IMPORTED = 0;  // 初始匯入
    const STATUS_UNUSED   = 2;  // 未使用
    const STATUS_USED     = 3;  // 已使用

    protected function _construct()
    {
        $this->_init(QwareTicketRecordModel::TABLE_NAME, QwareTicketRecordModel::ID_FIELD_NAME);
    }
}