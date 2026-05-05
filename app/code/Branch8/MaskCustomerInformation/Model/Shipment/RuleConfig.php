<?php
declare(strict_types=1);

namespace Branch8\MaskCustomerInformation\Model\Shipment;

use Branch8\MaskCustomerInformation\Model\RuleConfigInterface;

class RuleConfig implements RuleConfigInterface
{
    /**
     * @return string[]
     */
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
     * @return []
     */
    public function getDisableEditorFields()
    {
        return [];
    }

}
