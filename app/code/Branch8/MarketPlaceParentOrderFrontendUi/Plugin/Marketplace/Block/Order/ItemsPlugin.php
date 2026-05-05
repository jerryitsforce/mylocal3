<?php

namespace Branch8\MarketPlaceParentOrderFrontendUi\Plugin\Marketplace\Block\Order;

class ItemsPlugin{

    public function afterAddAdditionalFilters($subject, $result, $itemCollection){
        $result->getSelect()->columns(['main_table.base_cost', 'main_table.row_total_point_used']);

        return $result;
    }

}