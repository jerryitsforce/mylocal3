<?php

namespace HotaiConnected\OpenHub\Model\ResourceModel\OpenHubTicketRecord;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'record_id';

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \HotaiConnected\OpenHub\Model\OpenHubTicketRecord::class,
            \HotaiConnected\OpenHub\Model\ResourceModel\OpenHubTicketRecord::class
        );
    }
}