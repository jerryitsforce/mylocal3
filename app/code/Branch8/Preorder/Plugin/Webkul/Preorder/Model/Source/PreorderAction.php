<?php

namespace Branch8\Preorder\Plugin\Webkul\Preorder\Model\Source;

class PreorderAction{
    /**
     * @return array[]
     */
    public function afterToOptionArray(){
        $data = [
            ['value' => '0', 'label' => __("Per Product")],
        ];
        return $data;
    }
}