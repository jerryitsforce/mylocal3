<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       20/03/2026
 */

namespace Branch8\BlackListKeyWords\Model;

use Branch8\BlackListKeyWords\Api\KeywordFilterInterface;
use Branch8\BlackListKeyWords\Model\Actions\GetBlackListKeywords;
use Branch8\BlackListKeyWords\Model\AhoCorasick\MultiStringMatcher;
use Branch8\BlackListKeyWords\Model\ResourceModel\Keyword\CollectionFactory;


class KeywordFilter implements KeywordFilterInterface
{
    private MultiStringMatcher $matcher;
    private GetBlackListKeywords $getBlacklistKeyWords;

    /**
     * @param MultiStringMatcher $matcher
     * @param GetBlackListKeywords $getBlackListKeywords
     */
    public function __construct(
        MultiStringMatcher   $matcher,
        GetBlackListKeywords $getBlackListKeywords
    )
    {
        $this->getBlacklistKeyWords = $getBlackListKeywords;
        $this->matcher = $matcher;

    }

    /**
     * @return string[]
     */
    private function getBlacklistKeyWords()
    {
        return $this->getBlacklistKeyWords->get();
    }

    /**
     * @param string $text
     * @param $returnOnlyText
     * @return array|array[]|string
     */
    public function getMatchedKeywords(string $text, $returnOnlyText = false)
    {
        $keywords = $this->getBlacklistKeyWords();
        if(empty($keywords)) {
            return '';
        }
        $match = $this->matcher->init($keywords)->searchIn($text);
        if ($returnOnlyText && $match) {
            $words = array_map(function ($child) {
                return $child[1];
            }, $match);
            return $words ? join(',', $words) : '';
        }
        return $match;
    }
}
