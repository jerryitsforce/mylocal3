<?php

namespace Branch8\AppNotification\Api\Data;

interface CustomerNotificationResponseInterface
{

    /**
     * Get customer ID
     * @return int
     */
    public function getUnreadCount();

    /**
     * @param int $count
     * @return self
     */
    public function setUnreadCount($count);

    /**
     * @return int
     */
    public function getPageSize();

    /**
     * @param int $size
     * @return self
     */
    public function setPageSize($size);

    /**
     * @return int
     */
    public function getCurPage();

    /**
     * @param int $page
     * @return self
     */
    public function setCurPage($page);


    /**
     * @return int
     */
    public function getTotalCount();

    /**
     * @param int $count
     * @return self
     */
    public function setTotalCount($count);

    /**
     * @return \Branch8\AppNotification\Api\Data\NotificationInterface[]
     */
    public function getItems();

    /**
     * @param \Branch8\AppNotification\Api\Data\NotificationInterface[] $items
     * @return self
     */
    public function setItems(array $items);
}
