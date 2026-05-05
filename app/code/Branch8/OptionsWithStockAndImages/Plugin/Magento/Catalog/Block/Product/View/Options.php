<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\OptionsWithStockAndImages\Plugin\Magento\Catalog\Block\Product\View;

class Options
{
    public function afterGetOptions(
        \Magento\Catalog\Block\Product\View\Options $subject,
        $result
    ) {
        $options = [];
        foreach($result as $option){
            if($option->getValues()){
                $values = [];
                foreach($option->getValues() as $key => $value){
                    if($value->getData('is_visible')){
                        $values[] = $value;
                    }
                }
                $option->setValues($values);
            }
            $options[] = $option;
        }
        return $options;
    }
}