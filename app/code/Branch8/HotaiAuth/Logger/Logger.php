<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\HotaiAuth\Logger;

use Branch8\HotaiCore\Model\Detection\MobileDetect;
use DateTimeZone;
use Monolog\DateTimeImmutable;

/**
 * Class Logger
 * @package Branch8\Mezzofy\Logger
 */
class Logger extends \Monolog\Logger
{
    private MobileDetect $mobileDetect;

    public function __construct(
        string $name,
        MobileDetect $mobileDetect,
        array $handlers = [],
        array $processors = [],
        ?DateTimeZone $timezone = null
    )
    {
        $this->mobileDetect = $mobileDetect;
        parent::__construct($name, $handlers, $processors, $timezone);
    }

    public function addRecord($level, $message, array $context = [], ?DateTimeImmutable $datetime = null): bool
    {
       if ($this->mobileDetect->isHotaiApp()) {
           $context['device_uuid'] = $this->mobileDetect->getDeviceUUID() ?: null;
           return parent::addRecord($level, $message, $context, $datetime);
       }
       return false;
    }
}
