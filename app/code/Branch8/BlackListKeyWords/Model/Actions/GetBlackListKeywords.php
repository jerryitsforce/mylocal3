<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       22/03/2026
 */

namespace Branch8\BlackListKeyWords\Model\Actions;

use Branch8\BlackListKeyWords\Model\Cache\Type;
use Branch8\BlackListKeyWords\Model\ResourceModel\Keyword\CollectionFactory;
use Magento\Framework\App\CacheInterface;

class GetBlackListKeywords
{
    private CollectionFactory $collectionFactory;

    private CacheInterface $cache;

    /**
     * @param CollectionFactory $collectionFactory
     * @param CacheInterface $cache
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        CacheInterface    $cache,
    )
    {
        $this->cache = $cache;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @param $asArray
     * @return string|string[]
     */
    public function get($asArray = true)
    {
        if ($cached = (string)$this->cache->load(Type::TYPE_IDENTIFIER)) {
            $data = $cached;
        } else {
            $keywords = [];
            foreach ($this->getKeywordFromDatabase() as $keyword) {
                $keywords[] = $keyword;
            }
            $data = implode('|', array_unique($keywords));
            $this->cache->save($data, Type::TYPE_IDENTIFIER);
        }
        if (!$data) {
            return $asArray ? [] : "";
        } else {
            return $asArray ? explode('|', $data) : $data;
        }
    }

    /**
     * @param $pageSize
     * @return \Generator
     */
    private function getKeywordFromDatabase($pageSize = 100)
    {
        $page = 1;
        do {
            $collection = $this->collectionFactory->create();
            $collection->setPageSize($pageSize);
            $collection->setCurPage($page);
            $collection->load();
            if ($collection->count() == 0) {
                break;
            }
            foreach ($collection as $keyword) {
                yield $keyword->getKeyword();
            }
            // Free memory
            $collection->clear();
            $page++;
        } while ($page <= $collection->getLastPageNumber());
    }
}
