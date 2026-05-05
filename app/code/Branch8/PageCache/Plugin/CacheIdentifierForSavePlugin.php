<?php

namespace Branch8\PageCache\Plugin;

use Magento\PageCache\Model\App\Request\Http\IdentifierForSave;

class CacheIdentifierForSavePlugin
{
    const HOTAI_APP_CACHE_IDENTIFIER_PREFIX = 'HOTAI_APP_';


    /**
     * @param IdentifierForSave $subject
     * @param string $result
     * @return string
     */
    public function afterGetValue(IdentifierForSave $subject, string $result): string
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $isHotaiApp = stristr($userAgent, 'HotaiApp') !== false;
        if ($isHotaiApp) {
            $result = self::HOTAI_APP_CACHE_IDENTIFIER_PREFIX . $result;
        }

        return $result;
    }

}
