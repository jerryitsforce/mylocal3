<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Catalog\Plugin\Webkul\Marketplace\Ui\DataProvider\Product\Form\Modifier;

class AssignSeller
{

    public function afterGetSellerField(
        \Webkul\Marketplace\Ui\DataProvider\Product\Form\Modifier\AssignSeller $subject,
        $result
    ) {
        $result['arguments']['data']['config']['label'] = __('Special Dealer Name');
        $result['arguments']['data']['config']['required'] = 1;
        $result['arguments']['data']['config']['validation'] = ['required-entry' => true];
        return $result;
    }
}