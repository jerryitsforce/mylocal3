<?php

namespace Branch8\Customer\Plugin;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;

class ResetPasswordBackend
{
    protected $redirectFactory;

    protected $_customerRepository;

    protected $messageManager;
    public function __construct(
        \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory,
        CustomerRepositoryInterface $customerRepository,
        MessageManagerInterface  $messageManager
    )
    {
        $this->redirectFactory = $redirectFactory;
        $this->_customerRepository = $customerRepository;
        $this->messageManager = $messageManager;
    }
    public function aroundExecute($subject, $process)
    {
        $customerId = (int)$subject->getRequest()->getParam('customer_id', 0);
        if (!$customerId) {
            $resultRedirect = $this->rdr();
            return $resultRedirect;
        }
        $customer = $this->_customerRepository->getById($customerId);
        $platform = $customer->getCustomAttribute('platform')->getValue();
        if($platform != 'seller'){
            $resultRedirect = $this->rdr();
            $this->messageManager->addWarningMessage(__('You can not send forgot password email to Buyer.'));
            return $resultRedirect;
        }else{
            return $process();
        }


    }

    protected function rdr()
    {
        $resultRedirect = $this->redirectFactory->create();
        $resultRedirect->setPath('customer/index');
        return $resultRedirect;
    }

}