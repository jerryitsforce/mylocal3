<?php

namespace Branch8\Sales\Plugin\UiComponent\DataProvider;

use Magento\Framework\Api\Filter;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Collection;
use Magento\Framework\View\Element\UiComponent\DataProvider\FilterApplierInterface;
use Magento\Sales\Api\Data\OrderInterface;


class FilterApplier
{
    /**
     * Name space of the grid that passes to the mui render call.
     */
    private const GRID_NAMESPACE = [
        'sales_order_grid',
        'marketplacectrl_product_version_listing'
    ];

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * FilterApplier constructor.
     *
     * @param RequestInterface $request
     */
    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * This plugin will be executed before a filter is applied to the collection.
     * So each time a filter is applied in the sales order grid, we will check and if it is an ID filter
     * then we will change the condition of that filter from 'like' to 'in'. Also in the value there will be
     * %% symbols appended in the beginning and end of the value, we will remove that also.
     *
     * @param FilterApplierInterface $subject
     * @param Collection $collection
     * @param Filter $filter
     *
     * @return array
     */
    public function beforeApply(FilterApplierInterface $subject, Collection $collection, Filter $filter): array
    {
        $namespace = $this->request->getParam('namespace');
        if (in_array($namespace, self::GRID_NAMESPACE)) {
            if ($filter->getField() == OrderInterface::CREATED_AT) {
                $filter->setField('main_table.created_at');
            }
        }

        return [$collection, $filter];
    }
}
