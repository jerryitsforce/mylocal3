<?php

namespace Branch8\Preorder\Plugin;

class NotAllowImport{
    /**
     * @param $subject
     * @param $result
     * @param $data
     * @param $profileType
     * @param $row
     * @return mixed
     */
    public function afterValidateFields($subject, $result, $data, $profileType, $row){
        $data = $subject->prepareProductDataIfNotSet($data, $profileType);
        $product = $data['product'];
        if(isset($product['wk_marketplace_preorder']) || isset($product['preorder_mode'])
                || isset($product['preorder_start_date']) || isset($product['preorder_end_date'])
                || isset($product['wk_marketplace_availability']) || isset($product['preorder_use_qty'])
                || isset($product['wk_mppreorder_qty']) || isset($product['preorder_x_days'])
                || isset($product['preorder_ship_date'])
        ){
            $result['error'] = 1;
            $result['data'] = $data;
            $result['msg'] = __('Skipped row %1. Pre-order attributes can not be import .', $row);
        }
        return $result;
    }
}