<?php

namespace Branch8\Preorder\Plugin\Webkul\Preorder\Model\Source;

class PreorderType {
    /**
     * @return array[]
     */
    public function afterToOptionArray(){
        $data = [
            ['value'=>'0', 'label' => __("Complete Payment")]
        ];
        return $data;
    }


}