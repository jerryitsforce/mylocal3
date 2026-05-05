<?php

namespace Branch8\Catalog\Plugin\Marketplace\Ui\DataProvider\Product;

use Magento\Framework\Api\FilterBuilder;

class ProductDataProvider{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        FilterBuilder $filterBuilder
    ){
        $this->request = $request;
        $this->filterBuilder = $filterBuilder;
    }

    /**
     * @param \Magento\Catalog\Ui\DataProvider\Product\ProductDataProvider $subject
     * @param $result
     * @return mixed
     */
    public function beforeGetSearchResult($subject) {

        $filterData = $this->request->getParam('filters');
        if($subject->getName() == 'marketplace_products_listing_data_source' && !isset($filterData['is_hidden'])){
            $subject->addFilter(
                $this->filterBuilder->setField('is_hidden')
                    ->setValue(\Branch8\Catalog\Model\Source\HiddenTypeFull::NOT_HIDDEN)
                    ->setConditionType('eq')->create()
            );
        }

    }

}