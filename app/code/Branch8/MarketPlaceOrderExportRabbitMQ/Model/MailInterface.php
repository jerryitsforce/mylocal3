<?php

namespace Branch8\MarketPlaceOrderExportRabbitMQ\Model;

interface MailInterface
{
    /**
     * @param Profile $profile
     * @return mixed
     */
    public function send(Profile $profile);
}
