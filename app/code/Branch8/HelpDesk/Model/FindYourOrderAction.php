<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Model;

use Branch8\HelpDesk\Api\FindYourOrderServiceInterface;
use Magento\Framework\Api\FilterFactory;
use Magento\Framework\Api\Search\FilterGroupFactory;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;

/**
 * Find Customer Order
 */
class FindYourOrderAction implements FindYourOrderActionInterface
{
    public FindYourOrderServiceInterface $service;
    public SearchCriteriaBuilder $searchCriterialBuilder;
    public FilterFactory $filterFactory;
    public FilterGroupFactory $filterGroupFactory;

    /**
     * @param FindYourOrderServiceInterface $service
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterFactory $filterFactory
     * @param FilterGroupFactory $filterGroupFactory
     */
    public function __construct(
        FindYourOrderServiceInterface $service,
        SearchCriteriaBuilder         $searchCriteriaBuilder,
        FilterFactory                 $filterFactory,
        FilterGroupFactory            $filterGroupFactory
    )
    {
        $this->searchCriterialBuilder = $searchCriteriaBuilder;
        $this->filterFactory = $filterFactory;
        $this->filterGroupFactory = $filterGroupFactory;
        $this->service = $service;
    }

    /**
     * Search Customer Orders
     * @param $customerId
     * @param $page
     * @param $limit
     * @param $q
     * @return \Branch8\HelpDesk\Api\Data\FindYourOrdersSearchResultInterface
     */
    public function execute($customerId, $limit = 50, $page = 1, $q = '', $sort = 'entity_id', $sortDirection = 'ASC')
    {
        $customerFilter = $this->filterFactory->create();
        $customerFilter->setField('customer_id')->setValue(
            $customerId
        );
        $filterGroupRequired = $this->filterGroupFactory->create()->setFilters([$customerFilter]);
        $filterGroups = [$filterGroupRequired];
        if ($q) {
            $likeFilters = [];
            foreach ([
                         'increment_id',
                         'customer_email',
                         'customer_name',
                         'shipping_name',
                         'billing_name'
                     ]
                     as $field) {
                $likeFilters[] = $this->filterFactory->create()->setField(
                    $field
                )->setConditionType('like')->setValue('%' . $q . '%');
            }
            $filterGroups[] = $this->filterGroupFactory->create()->setFilters($likeFilters);
        }
        $this->searchCriterialBuilder
            ->setCurrentPage($page)
            ->setPageSize($limit);
        if ($sort) {
            $this->searchCriterialBuilder->addSortOrder($sort, $sortDirection);
        }
        $searchCriteria = $this->searchCriterialBuilder->create();
        $searchCriteria->setFilterGroups($filterGroups);
        return $this->service->search($searchCriteria);
    }
}
