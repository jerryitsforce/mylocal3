<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Customer\Controller\Info\Account;

use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Customer\Api\CustomerRepositoryInterface;

class Save extends \Magento\Framework\App\Action\Action
{

    /**
     * @var Validator
     */
    private $formKeyValidator;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var CustomerRepositoryInterface
     */
    protected CustomerRepositoryInterface $customerRepository;

    /**
     * Constructor
     *
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        Validator $formKeyValidator,
        Session $customerSession,
        CustomerRepositoryInterface $customerRepository
    ) {
        parent::__construct($context);
        $this->messageManager = $context->getMessageManager();
        $this->formKeyValidator = $formKeyValidator;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
    }

    /**
     * Execute view action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        if(!$this->customerSession->isLoggedIn()){
            return $resultRedirect->setPath('customer/account/login');
        }

        $referer = $this->_redirect->getRefererUrl();
        $isMemberInfoDetail = str_contains($referer, 'member/info/detail');

        $redirectPath = $isMemberInfoDetail ? 'member/info/detail' : 'customer/account';

        $validFormKey = $this->formKeyValidator->validate($this->getRequest());
        if ($validFormKey && $this->getRequest()->isPost()) {
            $data = $this->getRequest()->getParams();
            $customer = $this->customerRepository->getById($this->customerSession->getCustomerId());
            $customer->setCustomAttribute('nickname',$data['nickname']);
            $this->customerRepository->save($customer);
        }

        $this->messageManager->addSuccessMessage(__('已成功儲存暱稱'));
        return $resultRedirect->setPath($redirectPath, ['_query' => ['tab' => 'nickname']]);
    }
}

