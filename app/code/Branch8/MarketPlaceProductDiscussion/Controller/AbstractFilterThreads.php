<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       17/04/2026
 */

namespace Branch8\MarketPlaceProductDiscussion\Controller;

use Branch8\MarketPlaceProductDiscussion\Api\ProductDiscussionsQueryInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

abstract class AbstractFilterThreads
{
    protected const DATE_FIELDS = ['created_at', 'seller_replied_at'];
    protected const AVAIABLES_TABS = ['all', 'replied', 'unreplied'];

    protected RawFactory $resultRawFactory;
    protected JsonFactory $jsonFactory;
    protected \Magento\Framework\Json\Helper\Data $jsonHelper;
    protected RequestInterface $request;
    protected SearchCriteriaBuilder $searchCriteriaBuilder;
    protected FilterGroupBuilder $filterGroupBuilder;
    protected FilterBuilder $filterBuilder;
    protected ProductDiscussionsQueryInterface $productDiscussionsQuery;
    protected TimezoneInterface $timezone;

    /**
     * @param RawFactory $resultRawFactory
     * @param JsonFactory $jsonFactory
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param RequestInterface $request
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterGroupBuilder $filterGroupBuilder
     * @param FilterBuilder $filterBuilder
     * @param ProductDiscussionsQueryInterface $productDiscussionsQuery
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        RawFactory                       $resultRawFactory,
        JsonFactory                      $jsonFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        RequestInterface                 $request,
        SearchCriteriaBuilder            $searchCriteriaBuilder,
        FilterGroupBuilder               $filterGroupBuilder,
        FilterBuilder                    $filterBuilder,
        ProductDiscussionsQueryInterface $productDiscussionsQuery,
        TimezoneInterface                $timezone
    ) {
        $this->resultRawFactory = $resultRawFactory;
        $this->jsonFactory = $jsonFactory;
        $this->jsonHelper = $jsonHelper;
        $this->request = $request;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterGroupBuilder = $filterGroupBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->productDiscussionsQuery = $productDiscussionsQuery;
        $this->timezone = $timezone;
    }

    /**
     * @return bool
     */
    abstract protected function isAllowed(): bool;

    /**
     * @param string $tab
     * @param array $request
     * @return array
     */
    abstract protected function getTabData(string $tab, array $request): array;

    /**
     * Execute controller action.
     */
    public function execute()
    {
        $resultRaw = $this->resultRawFactory->create();
        $httpBadRequestCode = 404;

        try {
            $postDetail = $this->jsonHelper->jsonDecode($this->request->getContent());
        } catch (\Exception $e) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }

        if (empty($postDetail)
            || !$this->isAllowed()
            || empty($postDetail['tabs'])
            || $this->request->getMethod() !== 'POST'
            || $this->request->isXmlHttpRequest() === false
        ) {
            return $resultRaw->setHttpResponseCode($httpBadRequestCode);
        }

        $tabs = $postDetail['tabs'];
        $data = [];
        foreach ($tabs as $tab => $tabRequest) {
            if (!in_array($tab, static::AVAIABLES_TABS)) {
                continue;
            }
            list($items, $total, $page) = $this->getTabData($tab, $tabRequest);
            $data[$tab] = ['total' => $total, 'page' => $page, 'items' => $items];
        }

        return $this->jsonFactory->create()->setData([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * @param array $params
     * @return \Magento\Framework\Api\SearchCriteria
     */
    protected function getSearchCriteria(array $params)
    {
        $filterGroups = [];
        foreach ($params['filter_groups'] as $group) {
            $filters = [];
            foreach ($group['filters'] as $filter) {
                $value = in_array($filter['field'], static::DATE_FIELDS) && $filter['value'] ?
                    $this->convertToDatabaseDate($filter['value']) : $filter['value'];
                $filters[] = $this->filterBuilder->create()
                    ->setField($filter['field'])
                    ->setConditionType($filter['condition_type'] ?? 'eq')
                    ->setValue($value);
            }
            $filterGroups[] = $this->filterGroupBuilder
                ->setFilters($filters)
                ->create();
        }
        $this->searchCriteriaBuilder->setFilterGroups($filterGroups);
        if (!empty($params['pageSize'])) {
            $this->searchCriteriaBuilder->setPageSize($params['pageSize']);
        }
        if (!empty($params['currentPage'])) {
            $this->searchCriteriaBuilder->setCurrentPage($params['currentPage']);
        }
        return $this->searchCriteriaBuilder->create();
    }

    /**
     * @param string $string
     * @return string
     */
    protected function convertToDatabaseDate(string $string)
    {
        return $this->timezone
            ->date(\DateTime::createFromFormat('m/d/Y H:i:s', $string))
            ->setTime(0, 0, 0)
            ->format('Y-m-d H:i:s');
    }

    /**
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
