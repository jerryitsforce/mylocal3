<?php
namespace Branch8\RewardSystem\Logger;
use DateTimeImmutable;

class Logger extends \Monolog\Logger
{
    public function addRecord(int $level, string $message, array $context = [], ?DateTimeImmutable $datetime = null): bool{
        if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_RewardSystem', 'ars')){
            return parent::addRecord($level, $message, $context, $datetime);
        }
        return false;
    }
}
