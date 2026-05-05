<?php

namespace Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Model;

use Magento\Framework\Model\AbstractModel;

class Queue  extends AbstractModel
{
    const STATUS_PENDING = 'pending';
    const STATUS_ERROR = 'error';
    const STATUS_SUCCESS = 'success';
    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            \Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Model\ResourceModel\Queue::class
        );
    }
}
