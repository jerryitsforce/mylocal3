<?php

namespace Branch8\HotaiPoint\Controller\Detail;

use Branch8\HotaiPoint\Block\Detail\Row\ExpiringSoonRow;
use Branch8\HotaiPoint\Block\Detail\Row\PointHistoryRow;
use Branch8\HotaiPoint\Block\Detail\Row\PointRedeemedRow;
use Magento\Framework\DataObject;
use Magento\Framework\App\Http\Context as HttpContext;

class Ajax extends \Magento\Framework\App\Action\Action
{
    const PAGE_SIZE = 10;
    protected $resultJsonFactory;
    protected $customerSession;
    protected $pointHelper;
    protected $apiHelper;
    protected \Magento\Framework\View\LayoutInterface $layout;

    /**
     * @var HttpContext
     */
    protected HttpContext $httpContext;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Branch8\HotaiPoint\Helper\Data $pointHelper
     * @param \Branch8\HotaiPoint\Helper\Api $apiHelper
     * @param \Magento\Framework\View\LayoutInterface $layout
     * @param HttpContext $httpContext
     */
    public function __construct(
        \Magento\Framework\App\Action\Context                                       $context,
        \Magento\Framework\Controller\Result\JsonFactory                            $resultJsonFactory,
        \Magento\Customer\Model\Session                                             $customerSession,
        \Branch8\HotaiPoint\Helper\Data                                             $pointHelper,
        \Branch8\HotaiPoint\Helper\Api                                              $apiHelper,
        \Magento\Framework\View\LayoutInterface $layout,
        HttpContext $httpContext
    )
    {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerSession = $customerSession;
        $this->pointHelper = $pointHelper;
        $this->apiHelper = $apiHelper;
        $this->layout = $layout;
        parent::__construct($context);
        $this->httpContext = $httpContext;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $response = [
            'success' => false,
            'message' => 'Invalid request'
        ];

        if ($this->getRequest()->isAjax() && $this->getRequest()->isPost()) {
            $tab = $this->getRequest()->getPost('tab');


            switch ($tab) {
                case 'tab-expiring':
                    $response = $this->getExpiringPoints();
                    break;
                case 'tab-history':
                    $response = $this->getHistoryPoints();
                    break;
                case 'tab-redeemed':
                    $response = $this->getRedeemedPoints();
                    break;
            }

        }

        return $result->setData($response);
    }

    public function getExpiringPoints()
    {
        $curPage = $this->getRequest()->getPost('curPage', 1);

        $result = $this->apiHelper->getRecentMonthsDuePointsByCustomerId($this->customerSession->getCustomerId());

        $totalCount = count($result);
        $totalPage = ceil($totalCount / self::PAGE_SIZE);

        if ($curPage > $totalPage) {
            return [
                'success' => true,
                'html' => '',
                'hasMore' => false
            ];
        }

        $pagedResult = $this->paginateArray($result, self::PAGE_SIZE, $curPage);
        $html = '';
        foreach ($pagedResult as $item) {
            $html .= $this->getLayout()->createBlock(ExpiringSoonRow::class)
                ->addData($item)
                ->toHtml();
        }
        return [
            'success' => true,
            'html' => $html,
            'hasMore' => $curPage < $totalPage
        ];
    }

    protected function paginateArray($array, $pageSize, $currentPage) {
        $offset = ($currentPage - 1) * $pageSize;
        $pagedArray = array_slice($array, $offset, $pageSize);
        return $pagedArray;
    }



    public function getHistoryPoints()
    {
        $curPage = $this->getRequest()->getPost('curPage', 1);

        $from = $this->getRequest()->getPost('from');
        $from = $from ? $this->createDateFromFormat($from, 'Y/m/d') : null;
        $from = $from ?: null;

        $to = $this->getRequest()->getPost('to');
        $to = $to ? $this->createDateFromFormat($to, 'Y/m/d') : null;
        $to = $to ?: null;

        $result = $this->apiHelper->requestApiGetPointByOneid(
            (int) $this->httpContext->getValue('customer_id'),
            $this->apiHelper::QUERY_TYPE_TOTAL_AND_ADD_DETAIL,
            $curPage,
            self::PAGE_SIZE,
            $from,
            $to
        );

        $totalCount = $result["data"]["detailCount"];
        $totalPage = ceil($totalCount / self::PAGE_SIZE);


        $html = '';
        foreach ($result["data"]["detail"] as $item) {
            $item['uniqueId'] = uniqid();
            $html .= $this->getLayout()->createBlock(PointHistoryRow::class)
                ->addData($item)
                ->toHtml();
        }

        return [
            'success' => true,
            'html' => $html,
            'hasMore' => $curPage < $totalPage
        ];
    }

    public function getRedeemedPoints()
    {
        $curPage = $this->getRequest()->getPost('curPage', 1);

        $from = $this->getRequest()->getPost('from');
        $from = $from ? $this->createDateFromFormat($from, 'Y/m/d') : null;
        $from = $from ?: null;

        $to = $this->getRequest()->getPost('to');
        $to = $to ? $this->createDateFromFormat($to, 'Y/m/d') : null;
        $to = $to ?: null;

        $result = $this->apiHelper->requestApiGetPointByOneid(
            (int) $this->httpContext->getValue('customer_id'),
            $this->apiHelper::QUERY_TYPE_TOTAL_AND_DEDUC_DETAIL,
            $curPage,
            self::PAGE_SIZE,
            $from,
            $to
        );

        $totalCount = $result["data"]["detailCount"];
        $totalPage = ceil($totalCount / self::PAGE_SIZE);


        $html = '';
        foreach ($result["data"]["detail"] as $item) {
            $item['uniqueId'] = uniqid();
            $html .= $this->getLayout()->createBlock(PointRedeemedRow::class)
                ->addData($item)
                ->toHtml();
        }

        return [
            'success' => true,
            'html' => $html,
            'hasMore' => $curPage < $totalPage
        ];
    }

    public function getLayout()
    {
        return $this->layout;
    }


    public function createDateFromFormat(string $date, $format = 'Ymd')
    {
        return \DateTime::createFromFormat($format, $date);
    }
}
