<?php

namespace Branch8\Homepage\CustomerData;
use Magento\Customer\CustomerData\SectionSourceInterface;
class MemberBar implements SectionSourceInterface{

    protected $customerSession;

    public function __construct(
        \Magento\Customer\Model\Session $customerSession
    ){
        $this->customerSession = $customerSession;
    }

    public function getSectionData(){
        if($this->customerSession->isLoggedIn()){
            $customer = $this->customerSession->getCustomer();
            $name = (string)$customer->getLastname() . ' ' . (string)$customer->getFirstname();
            $memberBarData = [
                'isLoggedIn' => true,
                'name' => $name,
                'point_exp_in_month' => 300,
                'total_point' => 999999,
                'ticket' => 10,
                'time' => ''
            ];
        }else{
            $memberBarData = [
                'isLoggedIn' => false,
                'name' => '',
                'point_exp_in_month' => '',
                'total_point' => 0,
                'ticket' => 0,
                'time' => ''
            ];
        }

        return [
            'member_bar' => $memberBarData
        ];
    }
}