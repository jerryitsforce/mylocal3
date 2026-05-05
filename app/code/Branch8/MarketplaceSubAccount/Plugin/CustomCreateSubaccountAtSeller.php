<?php

namespace Branch8\MarketplaceSubAccount\Plugin;

use Magento\Customer\Model\EmailNotificationInterface;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory as SellerCollection;
use Webkul\SellerSubAccount\Api\SubAccountRepositoryInterface;
use Webkul\SellerSubAccount\Helper\Data as HelperData;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Webkul\SellerSubAccount\Model\SubAccount;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;

class CustomCreateSubaccountAtSeller
{
    /**
     * @var FormKeyValidator
     */
    protected $_formKeyValidator;
    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    protected $resultRedirectFactory;
    /**
     * @var HelperData
     */
    protected $_helper;
    /**
     * @var SubAccountRepositoryInterface
     */
    protected $_subAccountRepository;
    /**
     * @var MessageManagerInterface
     */
    protected $messageManager;
    /**
     * @var SubAccount
     */
    protected $_subAccount;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_date;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * @var \Branch8\MarketplaceSubAccount\Helper\Data
     */
    protected $b8SubAccountHelper;
    /**
     * @var CustomerCollectionFactory
     */
    protected $customerCollection;

    protected $customerCollectionFactory;

    protected $subAccountRepository;

    protected $sellerCollectionFactory;

    /**
     * @param FormKeyValidator $formKeyValidator
     * @param \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory
     * @param HelperData $helper
     * @param SubAccountRepositoryInterface $subAccountRepository
     * @param MessageManagerInterface $messageManager
     * @param SubAccount $subAccount
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Branch8\MarketplaceSubAccount\Helper\Data $b8SubAccountHelper
     * @param CustomerCollectionFactory $customerCollection
     */
    public function __construct(
        FormKeyValidator $formKeyValidator,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        HelperData $helper,
        SubAccountRepositoryInterface $subAccountRepository,
        MessageManagerInterface $messageManager,
        SubAccount $subAccount,
        \Magento\Framework\Stdlib\DateTime\DateTime $date,
        \Psr\Log\LoggerInterface $logger,
        \Branch8\MarketplaceSubAccount\Helper\Data $b8SubAccountHelper,
        CustomerCollectionFactory $customerCollection,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory,
        SellerCollection $sellerCollectionFactory
    ){
        $this->_formKeyValidator = $formKeyValidator;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->_helper = $helper;
        $this->_subAccountRepository = $subAccountRepository;
        $this->messageManager = $messageManager;
        $this->_subAccount = $subAccount;
        $this->_date = $date;
        $this->logger = $logger;
        $this->b8SubAccountHelper = $b8SubAccountHelper;
        $this->customerCollection = $customerCollection;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->sellerCollectionFactory = $sellerCollectionFactory;
    }

    public function aroundExecute($subject, $process)
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        if ($subject->getRequest()->isPost()) {
            try {
                if (!$this->_formKeyValidator->validate($subject->getRequest())) {
                    return $this->resultRedirectFactory->create()->setPath(
                        'sellersubaccount/account/manage',
                        ['_secure' => $subject->getRequest()->isSecure()]
                    );
                }
                $postData = $subject->getRequest()->getPostValue();
                if (empty($postData['permission_type'])) {
                    $postData['permission_type'] = [];
                }
                if (empty($postData['status'])) {
                    $postData['status'] = 0;
                }
                $parentAccountId = 0;
                $subAccountSellerId = 0;
                $subAccountActive = false;
                $sellerId = $this->_helper->getCustomerId();
                $currentSubAccount = $this->_helper->getCurrentSubAccount();
                if ($currentSubAccount->getId()) {
                    $subAccountSellerId = $currentSubAccount->getSellerId();
                    $subAccountActive = true;
                }
                $id = isset($postData['id'])
                    ? $postData['id']
                    : null;
                if (!empty($id)) {
                    // Check If sub account does not exists
                    $subAccount = $this->_subAccountRepository->get($id);
                    if ($subAccount->getId()) {
                        if ($currentSubAccount->getId()) {
                            $parentAccountId = $subAccount->getParentAccountId();
                            $condition = $parentAccountId == $sellerId;
                            $sellerId = $currentSubAccount->getSellerId();
                        } else {
                            $condition = $subAccount->getSellerId() == $sellerId;
                        }
                        if (($sellerId == $subAccount->getSellerId())) {
                            $validateSellerOrSub = $this->validateForEditSubAccount($sellerId, $postData['email']);
                            if(!$validateSellerOrSub){
                                return $this->resultRedirectFactory->create()->setPath(
                                    'sellersubaccount/account/edit',
                                    ['id'=>$id, '_secure' => $subject->getRequest()->isSecure()]
                                );
                            }
                            $currentCustomer = $this->customerCollection->create()
                                ->addFieldToFilter('entity_id', $subAccount->getCustomerId())
                                ->getFirstItem();
                            $currentCustomerData = $currentCustomer->getDataModel();
                            $subject->checkAndSaveCustomerData($id, $postData, $subAccount);

                            if($currentCustomer->getData('email') != $postData['email']) {
                                try {
                                    //send Email to old email
                                    $this->b8SubAccountHelper->sendMailToOldSubAccount($currentCustomerData);
                                } catch (\Exception $e) {}
                                try {
                                    //generate rp_token and send email to new email

                                    $this->b8SubAccountHelper->sendMailNewSubAccount($subAccount->getCustomerId(), EmailNotificationInterface::NEW_ACCOUNT_EMAIL_REGISTERED_NO_PASSWORD);
                                } catch (\Exception $e) {}
                            }
                        } else {
                            $this->messageManager->addError(
                                __('You are not authorized to update this sub account.')
                            );
                        }
                    } else {
                        $this->messageManager->addError(
                            __('Sub Account does not exist.')
                        );
                    }
                } else {
                    if (!$subject->validateCustomer($postData)) {
                        $this->messageManager->addError(
                            __('A customer with the same email address already exists in an associated website.')
                        );
                        return $this->resultRedirectFactory->create()->setPath(
                            'sellersubaccount/account/manage',
                            ['_secure' => $subject->getRequest()->isSecure()]
                        );
                    }
                    if ($subject->validateEmail($postData)) {
                        $result = $this->_helper->saveCustomerData($postData);
                        if (!empty($result['error']) && $result['error'] == 1) {
                            $this->messageManager->addError(
                                $result['message']
                            );
                            return $this->resultRedirectFactory->create()->setPath(
                                'sellersubaccount/account/manage',
                                ['_secure' => $subject->getRequest()->isSecure()]
                            );
                        } else {
                            $customerId = $result['customer_id'];
                        }
                        $value = $this->_subAccount;
                        $value->setSellerId($subAccountActive ? $subAccountSellerId : $sellerId);
                        $value->setCustomerId($customerId);
                        $value->setPermissionType(implode(',', $postData['permission_type']));
                        $value->setStatus($postData['status']);
                        $value->setParentAccountId($sellerId);
                        $value->setCreatedDate($this->_date->gmtDate());
                        $id = $value->save()->getId();

                        //send mail new sub account
                        try {
                            $this->b8SubAccountHelper->sendMailNewSubAccount($customerId, EmailNotificationInterface::NEW_ACCOUNT_EMAIL_REGISTERED_NO_PASSWORD);
                        }catch (\Exception $e){
                            $this->messageManager->addWarningMessage('Can not send email to Sub account.');
                        }
                        $this->messageManager->addSuccess(
                            __('SubAccount was succesfully created')
                        );
                        $this->messageManager->addNotice(
                            __('Please check your email %1 for your login details', $postData['email'])
                        );
                    } else {
                        $this->messageManager->addError(
                            __('Customer with this email registered already')
                        );
                    }

                }
                return $this->resultRedirectFactory->create()->setPath(
                    'sellersubaccount/account/edit',
                    ['id'=>$id, '_secure' => $subject->getRequest()->isSecure()]
                );
            } catch (\Exception $e) {
                $this->messageManager->addError('Unable to save Sub Account, please try again later.');
                if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_CatalogCustom', 'systemlog')){
                    $this->logger->info('Webkul_SellerSubAccount:'. $e->getMessage());
                }
                return $this->resultRedirectFactory->create()->setPath(
                    'sellersubaccount/account/manage',
                    ['_secure' => $subject->getRequest()->isSecure()]
                );
            }
        } else {
            return $this->resultRedirectFactory->create()->setPath(
                'sellersubaccount/account/manage',
                ['_secure' => $subject->getRequest()->isSecure()]
            );
        }
    }
    public function aroundValidateCustomer($subject, $process, $postData)
    {
        $customerCol = $this->customerCollection->create()
            ->addFieldToFilter('email', $postData['email']);
        if ($customerCol->getSize() > 0) {
            return false;
        }
        return true;
    }

    public function validateForEditSubAccount($sellerId, $email){
        //validate is a sub account?
        $customer = $this->customerCollectionFactory->create()
            ->addAttributeToSelect('entity_id')
            ->addFieldToFilter('email', $email)
            ->getFirstItem();
        if($customer->getId()){
            $subAccount = $this->_subAccountRepository->getByCustomerId($customer->getId());
            if($subAccount->getId() && $subAccount->getSellerId() != $sellerId){
                $this->messageManager->addErrorMessage(__('This email is a sub account of other seller.')->render());
                return false;
            }
        }

        //validate is this a seller
        $sellerColl = $this->sellerCollectionFactory->create()
            ->addFieldToSelect(['entity_id', 'seller_code'])
            ->addFieldToFilter('seller_id', $customer->getId())
            ->getFirstItem();
        if($sellerColl->getId()){
            $this->messageManager->addErrorMessage(__('This Sub account email is a seller.')->render());
            return false;
        }

        return true;
    }

}