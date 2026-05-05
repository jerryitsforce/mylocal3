<?php
declare(strict_types=1);

namespace Branch8\MaskCustomerInformation\Model\CreditNemo;

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
