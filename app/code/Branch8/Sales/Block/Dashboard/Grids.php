<?php

namespace Branch8\Sales\Block\Dashboard;

class Grids extends \Fastly\Cdn\Block\Dashboard\Grids
{
    /**
     * @return void
     * @throws \Exception
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $this->addTab(
            'best_vendors',
            [
                'label'     => __('Sales Statistics Line Chart'),
                'url'       => $this->getUrl('customchart/dashboard/productchart', ['_current' => true]),
                'class'     => 'ajax',
                'active'    => false
            ]
        );
    }
}
