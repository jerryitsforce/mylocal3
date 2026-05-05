<?php

namespace Branch8\Sales\Block\Dashboard\Tab\Products;

class Viewed extends \Magento\Backend\Block\Dashboard\Tab\Products\Viewed
{
    /**
     * Show 10 items for Most Viewed Products tabs
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('productsReviewedGrid');
        $this->setDefaultLimit(10);
    }
}
