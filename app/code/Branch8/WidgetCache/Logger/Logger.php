<?php
namespace Branch8\WidgetCache\Logger;

use Monolog\Logger as MonoLogger;
use DateTimeZone;

class Logger extends \Monolog\Logger
{
    public function __construct(
        string $name = 'widgetcache',
        array $handlers = [],
        array $processors = [],
        ?DateTimeZone $timezone = null
    ) {
        parent::__construct($name, $handlers, $processors, $timezone);
    }
}

