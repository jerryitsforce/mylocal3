<?php
declare(strict_types=1);


namespace Branch8\MaskCustomerInformation\Model;

class RuleConfig
{
    /**
     * Define relation between field and rule
     * @return string[]
     */
    public function getFieldRules()
    {
        $rules = [
            'member_seq' => 'phone',
            'firstname' => 'firstname',
            'telephone' => 'phone',
            'street' => 'address',
            'email' => 'email',
            'phone_number' => 'phone',
            'dob' => 'birthday'
        ];
        return $rules;
    }
}
