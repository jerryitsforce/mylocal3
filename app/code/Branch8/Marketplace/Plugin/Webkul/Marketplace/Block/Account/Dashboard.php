<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Branch8\Marketplace\Plugin\Webkul\Marketplace\Block\Account;

use Magento\Framework\App\ResourceConnection;
use Branch8\Marketplace\Service\MarketplaceLogger;

class Dashboard
{
    /**
     * @var \Magento\Catalog\Model\CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @var \Webkul\Marketplace\Model\ResourceModel\Saleslist\CollectionFactory
     */
    protected $mpSaleslistCollectionFactory;

    /**
     * @var ResourceConnection
     */
    protected $resource;
    private MarketplaceLogger $marketplaceLogger;

    /**
     * Construct
     *
     * @param ResourceConnection $resource
     * @param MarketplaceLogger $marketplaceLogger
     */
    public function __construct(
        \Webkul\Marketplace\Model\ResourceModel\Saleslist\CollectionFactory $mpSaleslistCollectionFactory,
        \Magento\Catalog\Model\CategoryRepository $categoryRepository,
        ResourceConnection $resource,
        MarketplaceLogger $marketplaceLogger
    ) {
        $this->mpSaleslistCollectionFactory = $mpSaleslistCollectionFactory;
        $this->categoryRepository = $categoryRepository;
        $this->resource = $resource;
        $this->marketplaceLogger = $marketplaceLogger;
    }

    /**
     * Get top sale category
     *
     * @return array
     */
    public function aroundGetTopSaleCategories($subject, $process)
    {
        $sellerId = $subject->getCustomerId();
        $collection = $this->mpSaleslistCollectionFactory->create()
        ->addFieldToFilter(
            'seller_id',
            $sellerId
        )
        ->addFieldToFilter(
            'parent_item_id',
            ['null' => 'true']
        )
        ->getAllOrderProducts();
        $name = '';
        $resultData = [];
        $catArr = [];
        $totalOrderedProducts = 0;
        foreach ($collection as $coll) {
            $totalOrderedProducts = $totalOrderedProducts + $coll['qty'];
        }
        $collection = $this->mpSaleslistCollectionFactory->create()
        ->addFieldToFilter(
            'seller_id',
            $sellerId
        )
        ->addFieldToFilter(
            'parent_item_id',
            ['null' => 'true']
        );
        if (count($collection) > 0) {
            $orderItemIds = [];
            foreach ($collection as $coll) {
                $orderItemIds[] = $coll['order_item_id'];
            }
            $orderItemIds = array_unique($orderItemIds);
            $connection = $this->resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
            $tblSalesOrderItem = $connection->getTableName('sales_order_item');
            $tblCategoryProduct = $connection->getTableName('catalog_category_product');
            $select = $connection->select()
                ->from(['soi' => $tblSalesOrderItem])
                ->joinLeft(
                    ['ccp' => $tblCategoryProduct],
                    'ccp.product_id = soi.product_id',
                    ['categories' => 'GROUP_CONCAT(ccp.category_id SEPARATOR ",")']
                )
                ->where('item_id IN (?)', $orderItemIds)
                ->group('soi.product_id');
            $result = $connection->fetchAll($select);
            $complexResults = [];
            foreach ($result as $row) {
                $complexResults[$row['item_id']] = $row['categories'];
            }
            if (!empty($complexResults)) {
                foreach ($collection as $coll) {
                    if (!empty($complexResults[$coll['order_item_id']])) {
                        $cats = explode(',', $complexResults[$coll['order_item_id']]);
                        foreach ($cats as $proCategory) {
                            if (!isset($catArr[$proCategory])) {
                                $catArr[$proCategory] = $coll['magequantity'];
                            } else {
                                $catArr[$proCategory] = $catArr[$proCategory] + $coll['magequantity'];
                            }
                        }
                    }
                }
            }
        }
        $categoryArr = [];
        $percentageArr = [];
        foreach ($catArr as $key => $value) {
            if ($value) {
                $percentageArr[$key] = round((($value * 100) / $totalOrderedProducts), 2);
            } else {
                $percentageArr[$key] = 0;
            }
            try {
                $categoryArr[$key] = $this->categoryRepository->get($key)->getName();
            } catch (\Exception $e) {
                $this->marketplaceLogger->logException('Dashboard', $e, [
                    'category_id' => $key,
                    'seller_id' => $sellerId,
                ]);
                unset($categoryArr[$key]);
            }
        }
        $resultData['percentage_arr'] = $percentageArr;
        $resultData['category_arr'] = $categoryArr;
        return $resultData;
    }
}
