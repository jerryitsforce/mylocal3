<?php

namespace Branch8\MarketPlaceSeller\Controller\Account;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\ForgotPasswordToken\ConfirmCustomerByToken;
use Magento\Customer\Model\ForgotPasswordToken\GetCustomerByToken;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class CreatePassword extends \Magento\Customer\Controller\Account\CreatePassword
{
    /**
     * @var ConfirmCustomerByToken
     */
    private $confirmByToken;

    /**
     * @var GetCustomerByToken
     */
    private $getByToken;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    public function __construct(
        Context $context,
        Session $customerSession,
        PageFactory $resultPageFactory,
        AccountManagementInterface $accountManagement,
        ConfirmCustomerByToken $confirmByToken = null,
        GetCustomerByToken $getByToken = null,
        CustomerRepositoryInterface $customerRepository = null
    ) {
        parent::__construct($context, $customerSession, $resultPageFactory, $accountManagement, $confirmByToken, $getByToken, $customerRepository);
        $this->confirmByToken = $confirmByToken
            ?? ObjectManager::getInstance()->get(ConfirmCustomerByToken::class);
        $this->getByToken = $getByToken
            ?? ObjectManager::getInstance()->get(GetCustomerByToken::class);
        $this->customerRepository = $customerRepository
            ?? ObjectManager::getInstance()->get(CustomerRepositoryInterface::class);
    }


}