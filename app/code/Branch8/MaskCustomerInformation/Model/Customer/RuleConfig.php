<?php
declare(strict_types=1);

namespace Branch8\MaskCustomerInformation\Model\Customer;

use Branch8\MaskCustomerInformation\Model\RuleConfigInterface;

class RuleConfig implements RuleConfigInterface
{
    /**
     * Define relation between field and rule
     * @return string[]
     */
    public function getRules()
    {
        /// field=>rule
        return [
            'member_seq' => 'phone',
            'firstname' => 'firstname',
            'telephone' => 'phone',
            'street' => 'address',
            'email' => 'email',
            'phone_number' => 'phone',
            // customer grid
            'buyer_email' => 'email',
            'nickname' => 'firstname',
            'name' => 'firstname',
            'billing_telephone' => 'phone',
            'billing_firstname' => 'firstname',
            'billing_lastname' => 'firstname',
            'billing_full' => 'firstname',
            'shipping_full' => 'firstname',
            'invoice_carrier' => 'invoice_carrier',
            'group_id' => 'group_id',
            'prefix' => 'prefix_name'
            //    'dob' => 'birthday'
        ];
    }

    /**
     * @return array
     */
    public function getDisableEditorFields()
    {
        return array_keys($this->getRules());
    }
}
