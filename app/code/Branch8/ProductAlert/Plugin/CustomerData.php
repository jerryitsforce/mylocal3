<?php

namespace Branch8\ProductAlert\Plugin;

use Magento\Customer\CustomerData\Customer;

class CustomerData
{

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\Session $customerSession
    )
    {
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
    }
    /**
     * @param Customer $subject
     * @param array $result
     * @return array
     */
    public function afterGetSectionData(Customer $subject, array $result): array
    {
        if($this->customerSession->getCustomerId()){
            $result['eighteen'] = $this->getCustomerAge($this->customerSession->getCustomerId());
        }
        $result['confirm_productalert'] = $this->customerSession->getConfirmProductAlert();
        return $result;
    }

    /**
     * @param $customerId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCustomerAge($customerId)
    {
        if($customerId) {
            $customer = $this->customerRepository->getById($customerId);
            $customerEighteen = $customer->getCustomAttribute('eighteen');
            if ($customerEighteen) {
                if($customerEighteen->getValue() == 1){
                    return true;
                }
            }
        }
        return false;
    }
}
