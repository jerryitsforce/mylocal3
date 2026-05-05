<?php
declare(strict_types=1);

namespace Branch8\MaskCustomerInformation\Model\Invoice;

use Branch8\MaskCustomerInformation\Model\RuleConfigInterface;

class RuleConfig implements RuleConfigInterface
{
    /**
     * Define relation between field and rule
     * @return string[]
     */
    public function getRules()
    {
        return [
            'billing_name' => 'firstname',
            'shipping_name' => 'firstname',
            // invoice grid
            'billing_address' => 'address',
            'shipping_address' => 'address',
            'customer_email' => 'email',
            'customer_name' => 'firstname',
        ];
    }

    public function getDisableEditorFields()
    {
        return [];
        // TODO: Implement getDisableEditorFields() method.
    }
}
