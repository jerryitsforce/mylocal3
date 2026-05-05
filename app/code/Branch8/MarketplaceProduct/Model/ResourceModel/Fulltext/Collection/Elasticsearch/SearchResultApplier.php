<?php

namespace Branch8\MarketplaceProduct\Model\ResourceModel\Fulltext\Collection\Elasticsearch;

use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Data\Collection;

class SearchResultApplier extends \Magento\Elasticsearch\Model\ResourceModel\Fulltext\Collection\SearchResultApplier
{

    private $collection;

    /**
     * @var SearchResultInterface
     */
    private $searchResult;

    /**
     * @var int
     */
    private $size;

    /**
     * @var int
     */
    private $currentPage;

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;
    public function __construct(
        Collection $collection,
        SearchResultInterface $searchResult,
        \Magento\Framework\App\Request\Http $request,
        int $size,
        int $currentPage
    ) {
        $this->collection = $collection;
        $this->searchResult = $searchResult;
        $this->size = $size;
        $this->currentPage = $currentPage;
        $this->request = $request;
        parent::__construct($collection, $searchResult, $size, $currentPage);
    }

    /**
     * @return void
     */
    public function apply()
    {
        if (empty($this->searchResult->getItems())) {
            if ($this->request->getFullActionName() != 'marketplace_seller_collection') {
                $this->collection->getSelect()->where('NULL');
            }
            return;
        }

        $items = $this->sliceItems($this->searchResult->getItems(), $this->size, $this->currentPage);
        $ids = [];
        foreach ($items as $item) {
            $ids[] = (int)$item->getId();
        }
        $orderList = implode(',', $ids);
        $this->collection->getSelect()
            ->where('e.entity_id IN (?)', $ids)
            ->reset(\Magento\Framework\DB\Select::ORDER)
            ->order(new \Zend_Db_Expr("FIELD(e.entity_id,$orderList)"));
    }

    /**
     * @param array $items
     * @param int $size
     * @param int $currentPage
     * @return array
     */
    private function sliceItems(array $items, int $size, int $currentPage): array
    {
        if ($size !== 0) {
            // Check that current page is in a range of allowed page numbers, based on items count and items per page,
            // than calculate offset for slicing items array.
            $itemsCount = count($items);
            $maxAllowedPageNumber = ceil($itemsCount/$size);
            if ($currentPage < 1) {
                $currentPage = 1;
            }
            if ($currentPage > $maxAllowedPageNumber) {
                $currentPage = $maxAllowedPageNumber;
            }

            $offset = $this->getOffset($currentPage, $size);
            $items = array_slice($items, $offset, $size);
        }

        return $items;
    }

    /**
     * Get offset for given page.
     *
     * @param int $pageNumber
     * @param int $pageSize
     * @return int
     */
    private function getOffset(int $pageNumber, int $pageSize): int
    {
        return ($pageNumber - 1) * $pageSize;
    }
}
