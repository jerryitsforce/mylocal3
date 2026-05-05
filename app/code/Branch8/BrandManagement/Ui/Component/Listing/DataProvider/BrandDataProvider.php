<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\BrandManagement\Ui\Component\Listing\DataProvider;

use Branch8\BrandManagement\Api\BrandOptionRepositoryInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider;
use Psr\Log\LoggerInterface;

/**
 * Brand Data Provider
 */
class BrandDataProvider extends DataProvider
{
    /**
     * @var BrandOptionRepositoryInterface
     */
    private $brandOptionRepository;

    /**
     * @var array
     */
    private $loadedData;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Constructor
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ReportingInterface $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param BrandOptionRepositoryInterface $brandOptionRepository
     * @param LoggerInterface $logger
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        BrandOptionRepositoryInterface $brandOptionRepository,
        LoggerInterface $logger,
        array $meta = [],
        array $data = []
    ) {
        $this->brandOptionRepository = $brandOptionRepository;
        $this->logger = $logger;
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
    }

/**
 * Get data
 *
 * @return array
 */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        try {
            $brandOptions = $this->brandOptionRepository->getBrandOptions();
            $items        = [];
            $totalCount   = 0;

            $searchCriteria = $this->getSearchCriteria();
            $filters        = $searchCriteria->getFilterGroups();

            // Handle fulltext search
            $searchTerm = $this->getSearchTerm();

            foreach ($brandOptions as $option) {
                $item = [
                    'option_id'  => $option['option_id'],
                    'value'      => $option['value'],
                    'sort_order' => $option['sort_order'],
                ];

                // Apply filters and search
                if ($this->applyFilters($item, $filters) && $this->applySearch($item, $searchTerm)) {
                    $items[] = $item;
                    $totalCount++;
                }
            }

            // Apply sorting
            $items = $this->applySorting($items);

            // Apply pagination
            $pageSize    = $this->getPageSize();
            $currentPage = $this->getCurrentPage();
            $offset      = ($currentPage - 1) * $pageSize;

            $paginatedItems = array_slice($items, $offset, $pageSize);

            $this->loadedData = [
                'totalRecords' => $totalCount,
                'items'        => $paginatedItems,
            ];

            return $this->loadedData;
        } catch (\Exception $e) {
            $this->logger->error($e);
            $this->loadedData = [
                'totalRecords' => 0,
                'items'        => [],
            ];
            return $this->loadedData;
        }
    }

    private function getSearchTerm()
    {
        $searchCriteria = $this->getSearchCriteria();
        $filters        = $searchCriteria->getFilterGroups();

        foreach ($filters as $filterGroup) {
            $groupFilters = $filterGroup->getFilters();
            foreach ($groupFilters as $filter) {
                if ($filter->getField() === 'fulltext') {
                    return $filter->getValue();
                }
            }
        }

        return '';
    }

    private function applySearch($item, $searchTerm)
    {
        if (empty($searchTerm)) {
            return true;
        }

        $searchTerm       = strtolower($searchTerm);
        $searchableFields = ['value']; // Add more fields as needed

        foreach ($searchableFields as $field) {
            if (isset($item[$field]) && stripos(strtolower($item[$field]), $searchTerm) !== false) {
                return true;
            }
        }

        return false;
    }

    private function applySorting($items)
    {
        $sortField     = $this->getSortField();
        $sortDirection = $this->getSortDirection();

        if (empty($sortField)) {
            return $items;
        }

        usort($items, function ($a, $b) use ($sortField, $sortDirection) {
            $aValue = $a[$sortField] ?? '';
            $bValue = $b[$sortField] ?? '';

            if (is_numeric($aValue) && is_numeric($bValue)) {
                $result = $aValue <=> $bValue;
            } else {
                $result = strcmp($aValue, $bValue);
            }

            return $sortDirection === 'desc' ? -$result : $result;
        });

        return $items;
    }

    private function getSortField()
    {
        $sorting = $this->request->getParam('sorting', []);
        return $sorting['field'] ?? '';
    }

    private function getSortDirection()
    {
        $sorting = $this->request->getParam('sorting', []);
        return $sorting['direction'] ?? 'asc';
    }

/**
 * Get page size from request
 *
 * @return int
 */
    private function getPageSize()
    {
        $pageSize = $this->request->getParam('paging')['pageSize'] ?? 20;
        return (int) $pageSize;
    }

/**
 * Get current page from request
 *
 * @return int
 */
    private function getCurrentPage()
    {
        $currentPage = $this->request->getParam('paging')['current'] ?? 1;
        return (int) $currentPage;
    }

    /**
     * Apply filters to item
     *
     * @param array $item
     * @param array $filters
     * @return bool
     */
    private function applyFilters($item, $filters)
    {
        if (empty($filters)) {
            return true;
        }

        foreach ($filters as $filterGroup) {
            $groupFilters = $filterGroup->getFilters();
            $groupMatch   = true;

            foreach ($groupFilters as $filter) {
                $field     = $filter->getField();
                $value     = $filter->getValue();
                $condition = $filter->getConditionType();

                // Skip fulltext filter as it's handled separately
                if ($field === 'fulltext') {
                    continue;
                }

                // Skip empty values
                if (empty($value) && $value !== '0') {
                    continue;
                }

                if (! $this->matchFilter($item, $field, $value, $condition)) {
                    $groupMatch = false;
                    break;
                }
            }

            if ($groupMatch) {
                return true;
            }
        }

        return false;
    }

/**
 * Match filter condition - Ultra simple version
 *
 * @param array $item
 * @param string $field
 * @param mixed $value
 * @param string $condition
 * @return bool
 */
    private function matchFilter($item, $field, $value, $condition)
    {
        if (!isset($item[$field])) {
            return false;
        }

        $itemValue = $item[$field];

        // Handle empty values
        if (empty($value) && $value !== '0') {
            return true;
        }

        // Simple string comparison for all cases
        $itemValue = (string)$itemValue;
        $value = (string)$value;

        // Only handle basic conditions
        switch ($condition) {
            case 'eq':
                return $itemValue === $value;
            case 'neq':
                return $itemValue !== $value;
            case 'like':
            case 'nlike':
                // Handle SQL LIKE patterns with % wildcards
                $pattern = str_replace('%', '', $value); // Remove % wildcards
                $contains = stripos($itemValue, $pattern) !== false;
                return $condition === 'nlike' ? !$contains : $contains;
            default:
                // For all other conditions, use simple string contains
                $contains = stripos($itemValue, $value) !== false;
                return $condition === 'nlike' ? !$contains : $contains;
        }
    }

/**
 * Match LIKE pattern with support for SQL wildcards
 *
 * @param string $itemValue
 * @param string $pattern
 * @return bool
 */
    private function matchLikePattern($itemValue, $pattern)
    {
        // Convert SQL LIKE pattern to regex pattern
        $regexPattern = str_replace(
            ['%', '_'],
            ['.*', '.'],
            preg_quote($pattern, '/')
        );

        // Add case-insensitive matching
        return (bool) preg_match('/^' . $regexPattern . '$/i', $itemValue);
    }

}
