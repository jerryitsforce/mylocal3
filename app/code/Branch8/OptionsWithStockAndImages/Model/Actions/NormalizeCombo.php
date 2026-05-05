<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       03/04/2026
 */

namespace Branch8\OptionsWithStockAndImages\Model\Actions;

class NormalizeCombo
{
    /**
     * @param string $combo
     * @param array $normalizeMap
     * @return string
     */
    public static function execute(string $combo, array $normalizeMap): string
    {
        if (empty($combo)) {
            return '';
        }
        $parts = explode('-', str_replace('_', '-', $combo));
        $parts = array_map('trim', $parts);
        sort($parts);
        $normalizedKey = implode('-', $parts);
        if (isset($normalizeMap[$normalizedKey])) {
            return $normalizeMap[$normalizedKey];
        }
        return $combo;
    }

    /**
     * @param string $combo
     * @return string
     */
    public static function getNormalizedKey(string $combo): string
    {
        if (empty($combo)) {
            return '';
        }
        $parts = explode('-', str_replace('_', '-', $combo));
        $parts = array_map('trim', $parts);
        sort($parts);
        return implode('-', $parts);
    }
}
