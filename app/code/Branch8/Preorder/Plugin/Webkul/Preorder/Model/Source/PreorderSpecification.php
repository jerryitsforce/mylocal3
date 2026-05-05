<?php

namespace Branch8\Preorder\Plugin\Webkul\Preorder\Model\Source;

class PreorderSpecification{

    /**
     * Options getter.
     *
     * @return array
     */
    public function afterToOptionArray()
    {
        $data = [
            ['value'=>'1', 'label' => __("All")]
        ];

        return $data;
    }

}