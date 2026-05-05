<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\MarketPlaceProductDiscussionCustomer\Block\Product\Discussions;

use Branch8\MarketPlaceProductDiscussion\Api\ProductDiscussionsQueryInterface;
use Branch8\MarketPlaceProductDiscussion\Model\Thread;
use Magento\Catalog\Model\Product;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\Template;

/**
 *
 */
class ListDiscussions extends \Magento\Framework\View\Element\Template
{
    private $productDiscussionsQuery;

    private $searchCriteriaBuilder;

    private $sortOrderBuilder;


    private $_coreRegistry;
    /**
     * @param Template\Context $context
     * @param ProductDiscussionsQueryInterface $productDiscussionsQuery
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        Template\Context                 $context,
        ProductDiscussionsQueryInterface $productDiscussionsQuery,
        SearchCriteriaBuilder            $searchCriteriaBuilder,
        SortOrderBuilder                 $sortOrderBuilder,
        \Magento\Framework\Registry      $registry,
        array                            $data = []
    )
    {
        $this->productDiscussionsQuery = $productDiscussionsQuery;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $collection = $this->getCollection();
        $totalCount = $collection->getSize();
        $product = $this->getProduct();

        // 👉 Page Title
        $this->pageConfig->getTitle()->set(
            __("全部問答(%1)", $totalCount)
        );

        // 👉 Breadcrumbs
        if ($breadcrumbs = $this->getLayout()->getBlock('breadcrumbs')) {
            $breadcrumbs->addCrumb('home', [
                'label' => __('Home'),
                'title' => __('Go to Home Page'),
                'link'  => $this->_storeManager->getStore()->getBaseUrl()
            ]);
            $breadcrumbs->addCrumb('product', [
                'label' => __('回到商品頁'),
                'title' => __('回到商品頁'),
                'link' => $product->getProductUrl()
            ]);
            $breadcrumbs->addCrumb('current', [
                'label' => __("全部問答(%1)", $totalCount),
                'title' => __("全部問答(%1)", $totalCount)
            ]);
        }

        return $this;
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\InputException
     */
    public function getCollection()
    {
        $pageSize = $this->_request->getParam('limit', 10);
        $page = $this->_request->getParam('page', 1);
        $this->searchCriteriaBuilder->addSortOrder(
            $this->sortOrderBuilder->create()->setDirection('desc')
                ->setField('created_at')
        )->addFilter('product_id', $this->getProduct()->getId())
            // ->addFilter('status', Thread::STATUS_APPROVED)
            ->addFilter('thread_type', Thread::THREAD_TYPE_QUESTION)
            ->setPageSize($pageSize)->setCurrentPage($page);
        return $this->productDiscussionsQuery->get(
            $this->searchCriteriaBuilder->create()
        );
    }

    /**
     * @return Product
     */
    public function getProduct()
    {
        return $this->_coreRegistry->registry('product');
    }

    /**
     * @param $collection
     * @param $name
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getCollectionPagerHtml($collection, $name)
    {
        return $this->getLayout()->createBlock(
            \Magento\Theme\Block\Html\Pager::class,
            $name
        )->setCollection($collection)
            ->setShowPerPage(false)
            ->setTemplate('Branch8_MarketPlaceProductDiscussionCustomer::product/pager.phtml')
            ->toHtml();
    }

    /**
     * @param $date
     * @return string
     */
    public function formatCreateDate($date)
    {
        return $this->_localeDate->formatDateTime(
            $date,
            \IntlDateFormatter::SHORT,
            \IntlDateFormatter::SHORT,
            null,
            null,
            'Y/MM/dd'
        );
    }

    /**
     * @param $thread
     * @return string
     */
    public function getLoadMoreUrl() {
        return $this->getUrl('');
    }

    /**
     * @param $string
     * @return string
     */
    public function escapeSpec($string)
    {
        return $this->escapeJs(json_decode('"' . $string . '"'));
    }
}
