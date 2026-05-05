<?php

namespace Branch8\ProductExportRabbitMQ\Model;

interface MailInterface
{
    /**
     * @param Profile $profile
     * @return mixed
     */
    public function send(Profile $profile);
}
