<?php

namespace Branch8\ProductAlert\Controller\Customer;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

class Update extends Action
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
     * @param Context $context
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        Context $context,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\Session $customerSession
    )
    {
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        parent::__construct($context);
    }

    /**
     * @return void
     */
    public function execute()
    {
        if($this->customerSession->getCustomerId()){
            try {
                $customer = $this->customerRepository->getById($this->customerSession->getCustomerId());
                $customer->setCustomAttribute('eighteen', true);
                $this->customerRepository->save($customer);
                $this->messageManager->addSuccessMessage(__('You are now 18 years old'));
            }catch (\Exception $e){
                $this->messageManager->addErrorMessage($e->getMessage());
            }

        }
    }
}
