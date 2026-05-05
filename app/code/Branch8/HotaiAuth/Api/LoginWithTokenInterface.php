<?php

namespace Branch8\HotaiAuth\Api;

interface LoginWithTokenInterface
{
    /**
     * Login with token
     *
     * @return string
     */
    public function login();
}