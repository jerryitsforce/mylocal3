<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       20/03/2026
 */

namespace Branch8\BlackListKeyWords\Api;

interface KeywordFilterInterface
{
    /**
     * @param string $text
     * @param $returnOnlyText
     * @return mixed
     */
    public function getMatchedKeywords(string $text, $returnOnlyText = false);

}
