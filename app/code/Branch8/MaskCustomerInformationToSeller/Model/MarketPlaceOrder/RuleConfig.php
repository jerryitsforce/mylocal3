<?php
declare(strict_types=1);

namespace Branch8\MaskCustomerInformationToSeller\Model\MarketPlaceOrder;

use Branch8\MaskCustomerInformation\Model\RuleConfigInterface;

class RuleConfig implements RuleConfigInterface
{
    public function getRules()
    {
        return [
            'billing_name' => 'firstname',
            'shipping_name' => 'firstname',
            // order grid
            'customer_name' => 'firstname',
            'billing_address' => 'address',
            'shipping_address' => 'address',
            'customer_email' => 'email',
            /// order detail
            'customer_firstname' => 'firstname',
            /// address
            'email' => 'email',
            'firstname' => 'firstname',
            'telephone' => 'phone',
            'phone' => 'phone',
            'street' => 'address'
        ];
    }

    /**
     * @return array
     */
    public function getDisableEditorFields()
    {
        return [];
    }
}
