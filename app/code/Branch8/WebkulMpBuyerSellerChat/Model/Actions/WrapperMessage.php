<?php

namespace Branch8\WebkulMpBuyerSellerChat\Model\Actions;

use \HTMLPurifier_Config;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Escaper;

class WrapperMessage
{

    /**
     * @param $input
     * @return string
     */
    public static function sanitize($input)
    {
        /**
         * @var $escaper Escaper
         */
        $escaper = null;
        if (!$escaper) {
            $escaper = ObjectManager::getInstance()->get(Escaper::class);
        }
        $clean = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $escaper->escapeHtml($input));
        return nl2br($clean);
    }

    /**
     * @param $html
     * @return string
     */
    public static function br2nl($html)
    {
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $html = strip_tags($html);
        return html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * @param $str
     * @return bool
     */
    public function containsHTMLRegex($str) {
        if (!$str) return false;
        return preg_match('/<[^>]+>/', $str) === 1;
    }
    /**
     * @param $input
     * @param $returnTextOnly
     * @return array|string|string[]|null
     */
    public static function unEscape($input, $returnTextOnly = true)
    {
        if (!$input) return "";
        $input = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($matches) {
            return mb_convert_encoding(pack('H*', $matches[1]), 'UTF-8', 'UCS-2BE');
        }, $input);
        if ($returnTextOnly) {
            $input = preg_replace('/<br\s*\/?>/i', "\n", $input);
            $input = preg_replace("/\r\n/", "\n", $input);
            $input = html_entity_decode($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        return $input;
    }
}
