<?php

namespace Branch8\AppNotification\Model\Data;


use Branch8\AppNotification\Api\Data\MarkAsReadResponseInterface;
use Magento\Framework\DataObject;

class MarkAsReadResponse extends DataObject implements MarkAsReadResponseInterface
{
    /**
     * @inheritDoc
     */
    public function getAllUnreadCount()
    {
        return (int)$this->getData(self::ALL_UNREAD_COUNT);
    }

    /**
     * @inheritDoc
     */
    public function setAllUnreadCount($allUnreadCount)
    {
        return $this->setData(self::ALL_UNREAD_COUNT, $allUnreadCount);
    }

    /**
     * @inheritDoc
     */
    public function getPersonalUnreadCount()
    {
        return (int)$this->getData(self::PERSONAL_UNREAD_COUNT);
    }

    /**
     * @inheritDoc
     */
    public function setPersonalUnreadCount($personalUnreadCount)
    {
        return $this->setData(self::PERSONAL_UNREAD_COUNT, $personalUnreadCount);
    }
}
