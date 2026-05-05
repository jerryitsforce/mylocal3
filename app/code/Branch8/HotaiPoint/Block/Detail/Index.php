<?php

namespace Branch8\HotaiPoint\Block\Detail;

use Branch8\HotaiPoint\Helper\Api as ApiHelper;
use Branch8\HotaiPoint\Helper\Data as PointHelper;
use Branch8\HotaiPoint\Model\ResourceModel\HotaiPointHistory\CollectionFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\App\Http\Context as HttpContext;

class Index extends AbstractBlock
{
    const PAGE_SIZE = 10;
    const TAB_PARAM = 't';
    const CUR_PAGE_PARAM = 'p';
    const FROM_DATE_PARAM = 'from';
    const TO_DATE_PARAM = 'to';

    const TABS = [
        0 => 'tab-expiring',
        1 => 'tab-history',
        2 => 'tab-redeemed'
    ];

    private CollectionFactory $collectionFactory;
    protected ApiHelper $apiHelper;
    protected Session $customerSession;

    protected $recentMonthsDuePoints = [];
    /**
     * @var HttpContext
     */
    protected HttpContext $httpContext;

    public function __construct(
        PointHelper       $pointHelper,
        ApiHelper         $apiHelper,
        CollectionFactory $collectionFactory,
        Session           $customerSession,
        Context           $context,
        HttpContext $httpContext,
        array             $data = []
    ) {
        $this->apiHelper = $apiHelper;
        $this->customerSession = $customerSession;
        $this->collectionFactory = $collectionFactory;

        parent::__construct($context, $pointHelper, $data);
        $this->httpContext = $httpContext;
    }

    public function getHotaiPointHistoryCollection()
    {
        $tabId = self::TABS[1];
        $pageIndex = $this->getCurPage($tabId);
        $startDate = $this->getFilterDate($tabId, self::FROM_DATE_PARAM);
        $endDate = $this->getFilterDate($tabId, self::TO_DATE_PARAM);


        $result = $this->apiHelper->requestApiGetPointByOneid(
            (int) $this->httpContext->getValue('customer_id'),
            $this->apiHelper::QUERY_TYPE_TOTAL_AND_ADD_DETAIL,
            $pageIndex,
            self::PAGE_SIZE,
            $startDate,
            $endDate
        );

        $collection = $this->collectionFactory->create();

        foreach ($result["data"]["detail"] as $history) {
            $variantObject = new \Magento\Framework\DataObject();
            $variantObject->setData($history);
            // $variantObject->setData('moveIn', 2);
            // $variantObject->setData('addPoint', 0);
            $collection->addItem($variantObject);
        }

        $collection->setSize($result["data"]["detailCount"])->setPageSize(self::PAGE_SIZE)->setCurPage($pageIndex);

        return $collection;
    }

    public function getRecentMonthsDuePoints()
    {
        if (empty($this->recentMonthsDuePoints)) {
            $this->recentMonthsDuePoints = $this->apiHelper->getRecentMonthsDuePointsByCustomerId($this->customerSession->getCustomerId());
        }

        return $this->recentMonthsDuePoints;
    }

    public function getExpiringSoonCollection()
    {
        $pageIndex = $this->getCurPage(self::TABS[0]);
        $result = $this->getRecentMonthsDuePoints();
        $totalCount = count($result);
        $collection = $this->collectionFactory->create();
        $pagedResult = $this->paginateArray($result, self::PAGE_SIZE, $pageIndex);

        foreach ($pagedResult as $item) {
            $object = new \Magento\Framework\DataObject();
            $object->setData($item);
            $collection->addItem($object);
        }

        $collection->setSize($totalCount)->setPageSize(self::PAGE_SIZE)->setCurPage($pageIndex);
        return $collection;
    }

    protected function paginateArray($array, $pageSize, $currentPage) {
        $offset = ($currentPage - 1) * $pageSize;
        $pagedArray = array_slice($array, $offset, $pageSize);
        return $pagedArray;
    }

    public function getClosetExpiringPoints()
    {
        $result = $this->getRecentMonthsDuePoints();

        if (empty($result)) {
            return [];
        }
        $currentDate = new \DateTime('now');
        $datePoints = [];

        foreach ($result as $item) {
            $dateStr = $item['date'];
            $date = $this->createDateFromFormat($dateStr);

            if ($date >= $currentDate) {
                if (!isset($datePoints[$dateStr]['point'])) {
                    $datePoints[$dateStr]['point'] = 0;
                    $datePoints[$dateStr]['datetime'] = $date;
                    $datePoints[$dateStr]['timestamp'] = $date->getTimestamp();
                }
                $datePoints[$dateStr]['point'] += $item['point'];
            }
        }

        if (empty($datePoints)) {
            return [];
        }

        uasort($datePoints, function($a, $b) {
            return $a['timestamp'] - $b['timestamp'];
        });

        $info = reset($datePoints);

        return [
            'date' => $info['datetime']->format('Y/m/d'),
            'point' => $info['point']
        ];
    }

    public function getRedeemedCollection()
    {
        $tabId = self::TABS[2];
        $pageIndex = $this->getCurPage($tabId);
        $startDate = $this->getFilterDate($tabId, self::FROM_DATE_PARAM);
        $endDate = $this->getFilterDate($tabId, self::TO_DATE_PARAM);

        $result = $this->apiHelper->requestApiGetPointByOneid(
            (int) $this->httpContext->getValue('customer_id'),
            $this->apiHelper::QUERY_TYPE_TOTAL_AND_DEDUC_DETAIL,
            $pageIndex,
            self::PAGE_SIZE,
            $startDate,
            $endDate
        );

        $collection = $this->collectionFactory->create();

        foreach ($result["data"]["detail"] as $history) {
            $variantObject = new \Magento\Framework\DataObject();
            $variantObject->setData($history);
            // $variantObject->setData('moveOut', 2);
            // $variantObject->setData('deductionPoint', 0);
            $collection->addItem($variantObject);
        }

        $collection->setSize($result["data"]["detailCount"])->setPageSize(self::PAGE_SIZE)->setCurPage($pageIndex);

        return $collection;

    }

    public function getHotaiPoint()
    {
        $result = $this->apiHelper->requestApiGetTransInfo($this->customerSession->getCustomerId());

        if ($this->apiHelper->getReturnCodeFromResponse($result) == $this->apiHelper::API_RESPONSE_CODE_GET_TRANS_INFO_NO_DATA) {
            return 0;
        }

        return $this->apiHelper->getPointFromResponse($result);
    }

    public function getActiveTab()
    {
        return $this->getRequest()->getParam(self::TAB_PARAM) ?? self::TABS[0];
    }

    public function getCurPage($tabId)
    {
        $result = 1;
        if ($this->getActiveTab() === $tabId) {
            $result = $this->getRequest()->getParam(self::CUR_PAGE_PARAM, $result);
            $result = $this->sanitizeInput($result);
            $result = $result ? (int)$result : 1;
        }
        return max($result, 1);
    }

    /**
     * @param $tabId
     * @param $param
     * @return \DateTime|null
     */
    public function getFilterDate($tabId, $param)
    {
        $result = null;
        if ($this->getActiveTab() === $tabId) {
            $result = $this->getRequest()->getParam($param, $result);
            $result = $this->sanitizeInput($result);
            $result = $result ? $this->createDateFromFormat($result, 'Y-m-d') : null;
            $result = $result ?: null; // createDateFromFormat failed
        }
        return $result;
    }

    private function getPageSizePoolForPager()
    {
        $pageSizePoolForPager = [];

        foreach ($this->apiHelper::PAGE_SIZE_POOL as $size) {
            $pageSizePoolForPager[$size] = $size;
        }

        return $pageSizePoolForPager;
    }

    public function getCollectionPagerHtml($collection, $name, $tab)
    {
        return $this->getLayout()->createBlock(
            \Branch8\HotaiPoint\Block\Html\CustomPager::class,
            $name
        )->setCollection($collection)
            ->setShowPerPage(false)
            ->setData('show_amounts', false)
            ->setData('tab', $tab)
            ->toHtml();
    }

    /**
     * @param \Branch8\HotaiPoint\Model\ResourceModel\HotaiPointHistory\Collection $collection
     * @return bool
     */
    public function hasMore($collection)
    {
        return $collection->getSize() > $collection->getCurPage() * $collection->getPageSize();
    }
}
