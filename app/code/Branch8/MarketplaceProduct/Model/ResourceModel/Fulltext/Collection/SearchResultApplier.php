<?php

namespace Branch8\MarketplaceProduct\Model\ResourceModel\Fulltext\Collection;

use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Data\Collection;
use Magento\Framework\DB\Select;

class SearchResultApplier extends \Magento\LiveSearchAdapter\Model\ResourceModel\Fulltext\Collection\SearchResultApplier
{
    /**
     * @var Collection
     */
    protected Collection $collection;

    /**
     * @var SearchResultInterface
     */
    protected SearchResultInterface $searchResult;


    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    public function __construct(
        Collection $collection,
        SearchResultInterface $searchResult,
        \Magento\Framework\App\Request\Http $request
    ) {
        $this->collection = $collection;
        $this->searchResult = $searchResult;
        $this->request = $request;
        parent::__construct($collection, $searchResult);
    }

    /**
     * Fix issue not display product from marketplace seller
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
        $ids = [];
        foreach ($this->searchResult->getItems() as $item) {
            $ids[] = $item->getId();
        }

        $orderList = implode(',', $ids);
        $this->collection->getSelect()->where('e.entity_id IN (?)', $ids);
        $this->collection->getSelect()
            ->where('e.entity_id IN (?)', $ids)
            ->reset(Select::ORDER)
            ->order(new \Zend_Db_Expr("FIELD(e.entity_id, $orderList)"));
    }
}
