<?php

namespace Branch8\MarketPlaceOrderExport\Model\Services;

use Branch8\HifiSalesReport\Helper\Common as CommonHelper;

class DateFormat
{
    public static function getChangeDayTitle(string $updateAt, $timeInclude = false): string
    {
        $l10nDate = new \DateTime($updateAt, new \DateTimeZone('UTC'));
        $newDateTime = clone $l10nDate;
        $newDateTime->setTimezone(new \DateTimeZone(CommonHelper::TIMEZONE));
        return $newDateTime->format($timeInclude ? "Y-m-d H:i:s" : "Y-m-d");
    }
}
