<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\SalesOrderGrid\Ui\Component\Listing\Column\Seller;

use Branch8\SalesOrderGrid\Model\Actions\GetSellerOptions;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\OptionSourceInterface;


/**
 * Class Options for Listing Column Status
 */
class Options implements OptionSourceInterface
{
    protected $options = null;

    private $getSellerOptions;
    private \Magento\Framework\View\Element\UiComponent\ContextInterface $context;

    /**
     * @param GetSellerOptions $gellerSellerOptions
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     */
    public function __construct(
        GetSellerOptions                                             $gellerSellerOptions,
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context
    )
    {
        $this->context = $context;
        $this->getSellerOptions = $gellerSellerOptions;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $key = 'seller_id';
        $filterData = $this->context->getFilterParam($key);
        $filters = [];
        if (is_array($filterData)) {
            $filters = [['field' => 'main_table.seller_id IN (?)', 'value' => $filterData]];
        }
        /* if (!isset($this->filterData[$key])) {
             return;
         }
         $this->context->getF*/
        if ($this->options === null) {
            if ($filters) {
                $sellerOptions = $this->getSellerOptions->get($filters);
            } else {
                $sellerOptions = $this->getSellerOptions->get();
            }
            $this->options = $sellerOptions['options'];
        }
        return $this->options;
    }
}
