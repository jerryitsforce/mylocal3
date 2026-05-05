<?php

namespace Branch8\PageCache\Plugin;

class CacheIdentifierPlugin
{
    const HOTAI_APP_CACHE_IDENTIFIER_PREFIX = 'HOTAI_APP_';
    /**
     * Modify the cache identifier value to include the customer group ID.
     *
     * @param \Magento\Framework\App\PageCache\Identifier $subject
     * @param string $result
     * @return string
     */
    public function afterGetValue(\Magento\Framework\App\PageCache\Identifier $subject, $result)
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $isHotaiApp = stristr($userAgent, 'HotaiApp') !== false;
        if ($isHotaiApp) {
            $result = self::HOTAI_APP_CACHE_IDENTIFIER_PREFIX . $result;
        }
        return $result;
    }
}
