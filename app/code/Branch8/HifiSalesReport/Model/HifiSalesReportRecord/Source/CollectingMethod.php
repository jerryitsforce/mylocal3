<?php

namespace Branch8\HifiSalesReport\Model\HifiSalesReportRecord\Source;

use Branch8\HifiSalesReport\Model\HifiSalesReportRecord;
use Branch8\HifiSalesReport\Helper\Common as CommonHelper;

class CollectingMethod extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /** @var CommonHelper */
    protected $commonHelper;

    public function __construct(
        CommonHelper $commonHelper
    ) {
        $this->commonHelper = $commonHelper;
    }

    public function getAllOptions()
    {
        if ($this->_options !== null) {
            return $this->_options;
        }

        $options = [];

        $options[] = ['label' => __('All'), 'value' => HifiSalesReportRecord::COLLECTING_METHOD_ALL];

        if ($this->commonHelper->getPaymentMethodsBelongToCreditCard()) {
            $options[] = ['label' => __('Credit Card'), 'value' => HifiSalesReportRecord::COLLECTING_METHOD_CREDIT];
        }

        if ($this->commonHelper->getPaymentMethodsBelongToConvenientStore()) {
            $options[] = ['label' => __('Convenient Store Collecting'), 'value' => HifiSalesReportRecord::COLLECTING_METHOD_CONVENIENT_STORE];
        }

        return $options;
    }

    public static function getOptionArray()
    {
        return [
            HifiSalesReportRecord::COLLECTING_METHOD_ALL              => __('All'),
            HifiSalesReportRecord::COLLECTING_METHOD_CREDIT           => __('Credit Card'),
            HifiSalesReportRecord::COLLECTING_METHOD_CONVENIENT_STORE => __('Convenient Store Collecting'),
        ];
    }
}