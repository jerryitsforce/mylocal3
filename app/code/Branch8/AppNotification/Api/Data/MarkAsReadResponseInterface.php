<?php
namespace Branch8\AppNotification\Api\Data;

interface MarkAsReadResponseInterface
{
    const ALL_UNREAD_COUNT = 'all_unread_count';
    const PERSONAL_UNREAD_COUNT = 'personal_unread_count';


    /**
     * @return int
     */
    public function getAllUnreadCount();

    /**
     * @param int $allUnreadCount
     * @return MarkAsReadResponseInterface
     */
    public function setAllUnreadCount($allUnreadCount);

    /**
     * @return int
     */
    public function getPersonalUnreadCount();


    /**
     * @param int $personalUnreadCount
     * @return MarkAsReadResponseInterface
     */
    public function setPersonalUnreadCount($personalUnreadCount);
}
