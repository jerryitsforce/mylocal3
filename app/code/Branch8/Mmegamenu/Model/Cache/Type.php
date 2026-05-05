<?php
namespace Branch8\Mmegamenu\Model\Cache;

use Magento\Framework\App\Cache\Type\FrontendPool;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;

class Type extends TagScope
{
    /**
     * Cache type code unique among all cache types
     */
    const TYPE_IDENTIFIER = 'branch8_megamenu_cache';

    /**
     * Cache tag used to distinguish the cache type from all other cache
     */
    const CACHE_TAG = 'BRANCH8_MEGAMENU';

    /**
     * Cache lifetime (in seconds)
     */
    const CACHE_LIFETIME = 864000;

    /**
     * @param FrontendPool $cacheFrontendPool
     * @codeCoverageIgnore
     */
    public function __construct(FrontendPool $cacheFrontendPool)
    {
        parent::__construct(
            $cacheFrontendPool->get(self::TYPE_IDENTIFIER),
            self::CACHE_TAG
        );
    }
}
