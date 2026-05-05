<?php

namespace Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Model\ResourceModel\Queue;
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Model\Queue::class,
            \Branch8\WebkulMpBuyerSellerChatUnreadMessageReminder\Model\ResourceModel\Queue::class
        );
    }
}
