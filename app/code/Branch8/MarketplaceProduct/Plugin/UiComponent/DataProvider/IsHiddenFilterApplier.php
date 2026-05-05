<?php

namespace Branch8\MarketplaceProduct\Plugin\UiComponent\DataProvider;

use Magento\Framework\Api\Filter;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Data\Collection;
class IsHiddenFilterApplier
{
    protected $request;

    public function __construct(
        Http $request
    )
    {
        $this->request = $request;
    }

    public function beforeApply($subject, Collection $collection, Filter $filter)
    {
        $namespace = $this->request->getParam('namespace');
        if ($namespace == 'marketplace_products_listing') {
            if ($filter->getField() == 'is_hidden') {
                $isHiddenValue = $filter->getValue();
                if($isHiddenValue == \Branch8\Catalog\Model\Source\HiddenTypeFull::All){
                    $filter->setConditionType('in');
                    $filter->setValue('1,2');
                }
            }
        }


        return [$collection, $filter];
    }
}