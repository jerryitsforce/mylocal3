<?php
namespace Magenest\NotificationBox\Api;

/**
 * Interface SaveTokenInterface
 * @package Magenest\NotificationBox\Api
 */
interface SaveTokenInterface {

    /** save customer Token
     * @param string $token
     * @param int $customerId
     * @param int $deviceId
     * @return mixed[]
     */
    public function registerForCustomer($token,$customerId,$deviceId);

    /** save guest Token
     * @param string $token
     * @param int $deviceId
     * @return mixed[]
     */
    public function registerForGuest($token,$deviceId);
}
