<?php

namespace Branch8\AppNotification\Api;

interface NotificationServiceInterface
{
    /**
     * Send a push notification to a device using FCM.
     *
     * @param string $deviceToken The device token to send the notification to.
     * @param string $title The title of the notification.
     * @param string $body The body of the notification.
     * @param array $data Additional data to include with the notification.
     * @return $this
     */
    public function send(string $deviceToken, string $title, string $body, array $data = [], int $badge = 0);


    /**
     * @param array $deviceTokens
     * @param string $title
     * @param string $body
     * @param array $data
     * @return $this
     */
    public function sendBatch(array $deviceTokens, string $title, string $body, array $data = [], array $badgeByToken = []);

}
