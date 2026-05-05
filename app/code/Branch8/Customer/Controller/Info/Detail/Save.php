<?php

declare(strict_types=1);

namespace Branch8\Customer\Controller\Info\Detail;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Message\ManagerInterface;

class Save extends Action
{
    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

    /**
     * @var FormKeyValidator
     */
    private FormKeyValidator $formKeyValidator;

    /**
     * @var CustomerSession
     */
    private CustomerSession $customerSession;

    /**
     * Save constructor.
     *
     * @param Context $context
     * @param FormKeyValidator $formKeyValidator
     * @param CustomerSession $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        Context                     $context,
        FormKeyValidator            $formKeyValidator,
        CustomerSession             $customerSession,
        CustomerRepositoryInterface $customerRepository
    ) {
        parent::__construct($context);
        $this->messageManager = $context->getMessageManager();
        $this->formKeyValidator = $formKeyValidator;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        if (!$this->customerSession->isLoggedIn()) {
            return $resultRedirect->setPath('customer/account/login');
        }

        $referer = $this->_redirect->getRefererUrl();
        $isMemberInfoDetail = str_contains($referer, 'member/info/detail');

        $redirectPath = $isMemberInfoDetail ? 'member/info/detail' : 'customer/account';

        $validFormKey = $this->formKeyValidator->validate($this->getRequest());
        if ($validFormKey && $this->getRequest()->isPost()) {
            $invoiceCarrier = $this->getRequest()->getParam('invoice_carrier');
            try {
                $customer = $this->customerRepository->getById($this->customerSession->getCustomerId());
                $customer->setCustomAttribute('invoice_carrier', $invoiceCarrier);
                $this->customerRepository->save($customer);
                $this->messageManager->addSuccessMessage(__('已成功儲存發票載具'));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
            return $resultRedirect->setPath($redirectPath, ['_query' => ['tab' => 'invoice_carrier']]);
        }

        $this->messageManager->addErrorMessage(__('Something went wrong while saving.'));
        return $resultRedirect->setPath($redirectPath, ['_query' => ['tab' => 'invoice_carrier']]);
    }
}
