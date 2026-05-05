<?php
namespace Branch8\HotaiPoint\Api;

/**
 * Interface SaveTokenInterface
 * @package Magenest\NotificationBox\Api
 */
interface AjaxInterface {

    /**
     * Transfer point ajax
     * @return mixed
     */
    public function transferPointAjax();

    /**
     * Transfer sms ajax
     * @return mixed
     */
    public function getMemberOTPSendAjax();

    /**
     * Register point ajax
     * @return mixed
     */
    public function registerPointAjax();
}
