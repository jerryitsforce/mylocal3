<?php
namespace Branch8\AppNotification\Model\Data;

use Branch8\AppNotification\Api\Data\CustomerNotificationResponseInterface;
use Magento\Framework\DataObject;

class CustomerNotificationResponse extends DataObject implements CustomerNotificationResponseInterface
{
    public function getUnreadCount()
    {
        return $this->getData('unread_count');
    }

    public function setUnreadCount($count)
    {
        return $this->setData('unread_count', $count);
    }

    public function getPageSize()
    {
        return $this->getData('page_size');
    }

    public function setPageSize($size)
    {
        return $this->setData('page_size', $size);
    }

    public function getCurPage()
    {
        return $this->getData('cur_page');
    }

    public function setCurPage($page)
    {
        return $this->setData('cur_page', $page);
    }

    public function getTotalCount()
    {
        return $this->getData('total_count');
    }

    public function setTotalCount($count)
    {
        return $this->setData('total_count', $count);
    }

    public function getItems()
    {
        return $this->getData('items');
    }

    public function setItems(array $items)
    {
        return $this->setData('items', $items);
    }
}


