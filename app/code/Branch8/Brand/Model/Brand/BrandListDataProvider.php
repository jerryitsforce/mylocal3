<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Shop by Brand for Magento 2
 */

namespace Branch8\Brand\Model\Brand;

class BrandListDataProvider
{
    /**
     * @var ListDataProvider\LoadItems
     */
    private $loadItems;

    /**
     * @var \Amasty\ShopbyBrand\Model\Brand\ListDataProvider\SortItems
     */
    private $sortItems;

    /**
     * @var \Amasty\ShopbyBrand\Model\Brand\ListDataProvider\FilterItems
     */
    private $filterItems;

    public function __construct(
        ListDataProvider\LoadItems $loadItems,
        \Amasty\ShopbyBrand\Model\Brand\ListDataProvider\SortItems $sortItems,
        \Amasty\ShopbyBrand\Model\Brand\ListDataProvider\FilterItems $filterItems
    ) {
        $this->loadItems = $loadItems;
        $this->sortItems = $sortItems;
        $this->filterItems = $filterItems;
    }

    /**
     * @param int $storeId
     * @param array $filterParams where keys is a filter names
     * @param string|null $sortBy
     *
     * @return BrandDataInterface[]
     */
    public function getList(int $storeId, array $filterParams = [], ?string $sortBy = null, $categoryId = null, $listBrand = []): array
    {
        $items = $this->loadItems->getItems($storeId, null, $categoryId, $listBrand);

        if (!empty($filterParams)) {
            $items = $this->filterItems->execute($items, $filterParams);
        }
        if (!empty($sortBy)) {
            $items = $this->sortItems->execute($items, $sortBy);
        }

        return $items;
    }
}
