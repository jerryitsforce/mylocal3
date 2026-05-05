<?php

namespace Branch8\Customer\Plugin;

use Magento\Customer\Api\CustomerRepositoryInterface;

class AccountManagement
{
    protected $customerRepository;

    public function __construct(
        CustomerRepositoryInterface $customerRepository
    )
    {
        $this->customerRepository = $customerRepository;
    }
    public function aroundInitiatePasswordReset($subject, $process, $email, $template, $websiteId = null)
    {
        $customer = $this->customerRepository->get($email);
        $platform = $customer->getCustomAttribute('platform')->getValue();
        if($platform != 'seller'){
            return true;
        }
        return $process($email, $template, $websiteId);
    }
}