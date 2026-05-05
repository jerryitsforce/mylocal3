<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model\Ticket;
/**
 * Format Ticket Code
 */
class CodeFormatter
{
    /**
     * Format code
     * @param $value
     * @return string
     */
    public static function format($value)
    {
        return sprintf("#%010d", trim($value));
    }
}
