<?php

namespace Branch8\RestrictedProduct\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class Handler extends Base
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/allow_customer_groups.log';

    /**
     * @var int
     */
    protected $loggerType = Logger::DEBUG;
}
