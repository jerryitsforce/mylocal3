<?php
namespace Branch8\Spin2Win\Logger;

class Logger extends \Webkul\SpinToWin\Logger\Logger
{
    public function addRecord(int $level, string $message, array $context = [], ?\DateTimeImmutable $datetime = null): bool
    {
        if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_Spin2Win', 'spin2win')){
            return parent::addRecord($level, $message, $context, $datetime);
        }
        return false;
    }
}
