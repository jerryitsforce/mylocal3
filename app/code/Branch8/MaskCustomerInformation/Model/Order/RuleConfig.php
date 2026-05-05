<?php
declare(strict_types=1);

namespace Branch8\MaskCustomerInformation\Model\Order;

use Branch8\MaskCustomerInformation\Model\RuleConfigInterface;

class RuleConfig implements RuleConfigInterface
{
    public function getRules()
    {
        return [
            'billing_name' => 'firstname',
            'shipping_name' => 'firstname',
            // order grid
            'billing_address' => 'address',
            'shipping_address' => 'address',
            'customer_email' => 'email',
            'customer_name' => 'firstname',
            /// order detail collection
            'customer_firstname' => 'firstname',
            /// address collection
            'email' => 'email',
            'firstname' => 'firstname',
            'telephone' => 'phone',
            'phone' => 'phone',
            'street' => 'address',
            /// report collection
            'customer' => 'firstname',
            'name' => 'firstname',
        ];
    }

    /**
     * @return []
     */
    public function getDisableEditorFields()
    {
        return [];

    }
}
