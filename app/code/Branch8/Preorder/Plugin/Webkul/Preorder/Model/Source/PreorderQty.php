<?php

namespace Branch8\Preorder\Plugin\Webkul\Preorder\Model\Source;

class PreorderQty{
    /**
     * The flag to use qty is in admin product
     *
     * @return array
     */
    public function afterToOptionArray(){
        $data = [
            ['value'=>'1', 'label' => __("Enable")],
        ];

        return $data;
    }
}