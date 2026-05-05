<?php

namespace Branch8\MarketplaceSubAccount\Plugin;

use Magento\Customer\Model\EmailNotificationInterface;
use mysql_xdevapi\Exception;
use Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory as SellerCollection;
use Webkul\SellerSubAccount\Api\SubAccountRepositoryInterface;

class SendMainAdminCreateSubAccount
{
    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    protected $resultRedirectFactory;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    /**
     * @var \Branch8\MarketplaceSubAccount\Helper\Data
     */
    protected $b8SubAccountHelper;
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;
    /**
     * @var \SubAccountRepositoryInterface
     */
    protected $subAccountRepository;
    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    protected $customerCollectionFactory;

    protected $sellerCollectionFactory;

    /**
     * @param \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Branch8\MarketplaceSubAccount\Helper\Data $b8SubAccountHelper
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param SubAccountRepositoryInterface $subAccountRepository
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory
     */
    public function __construct(
        \Magento\Framework\Controller\Result\RedirectFactory $redirectFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Branch8\MarketplaceSubAccount\Helper\Data $b8SubAccountHelper,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        SubAccountRepositoryInterface $subAccountRepository,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory,
        SellerCollection $sellerCollectionFactory
    ){
        $this->resultRedirectFactory = $redirectFactory;
        $this->messageManager = $messageManager;
        $this->b8SubAccountHelper = $b8SubAccountHelper;
        $this->customerRepository = $customerRepository;
        $this->subAccountRepository = $subAccountRepository;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->sellerCollectionFactory = $sellerCollectionFactory;
    }

    public function aroundExecute($subject, $process){
        $returnToEdit = false;
        $postData = $subject->getRequest()->getPostValue();
        //getCurrentAccountId
        $originalRequestData = $subject->getRequest()->getPostValue('sub_account');
        $id = isset($originalRequestData['entity_id'])
            ? $originalRequestData['entity_id']
            : null;

        //getCurrentSellerId
//        $originalRequestData = $this->getRequest()->getPostValue('sub_account');
        $sellerId = isset($originalRequestData['seller_id'])
            ? $originalRequestData['seller_id']
            : null;


        if ($subject->getRequest()->isPost()) {
            try {
                $postData = $subject->validateData($postData);
                if (!empty($id)) {
                    // Check If sub account does not exists
                    $subAccount = $subject->_subAccountRepository->get($id);
                    if ($subAccount->getId()) {
                        $currentCustomer = $this->customerRepository->getById($subAccount->getCustomerId());
                        $result = $subject->_helperData->saveCustomerData(
                            $postData['sub_account'],
                            $subAccount->getCustomerId(),
                            $subject->_marketplaceHelper->getWebsiteId()
                        );
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
                        $value = $subject->_subAccount->load($id);
                        $value->setPermissionType(
                            implode(',', $postData['sub_account']['permission_type'])
                        );
                        $value->setCustomerId($customerId);
                        $value->setStatus($postData['sub_account']['status']);
                        $value->save();

                        /*
                         * send mail to old & new account if changed email
                         */
                        if($postData['sub_account']['email'] != $currentCustomer->getEmail()){
                            try {
                                //send Email to old email
                                $this->b8SubAccountHelper->sendMailToOldSubAccount($currentCustomer);
                            } catch (\Exception $e) {}
                            try {
                                //generate rp_token and send email to new email
                                $this->b8SubAccountHelper->sendMailNewSubAccount($subAccount->getCustomerId(), EmailNotificationInterface::NEW_ACCOUNT_EMAIL_REGISTERED_NO_PASSWORD);
                            } catch (\Exception $e) {}
                        }

                        $this->messageManager->addSuccess(
                            __('Sub Account was successfully saved.')
                        );
                        $returnToEdit = (bool)$subject->getRequest()->getParam('back', false);
                    } else {
                        $this->messageManager->addError(
                            __('Sub Account does not exist.')
                        );
                        $returnToEdit = false;
                    }
                } else {
                    $result = $subject->_helperData->saveCustomerData($postData['sub_account']);
                    if (!empty($result['error']) && $result['error'] == 1) {
                        $this->messageManager->addError(
                            $result['message']
                        );
                        return $this->resultRedirectFactory->create()->setPath(
                            'sellersubaccount/account/manage',
                            [
                                'seller_id'=>$sellerId,
                                '_current' => true,
                                '_secure' => $subject->getRequest()->isSecure()
                            ]
                        );
                    } else {
                        $customerId = $result['customer_id'];
                    }
                    $value = $subject->_subAccount;
                    $value->setSellerId($sellerId);
                    $value->setCustomerId($customerId);
                    $value->setPermissionType(
                        implode(',', $postData['sub_account']['permission_type'])
                    );
                    $value->setStatus($postData['sub_account']['status']);
                    $value->setCreatedDate($subject->_date->gmtDate());
                    $id = $value->save()->getId();
                    $this->b8SubAccountHelper->sendMailNewSubAccount($customerId, EmailNotificationInterface::NEW_ACCOUNT_EMAIL_REGISTERED_NO_PASSWORD);
                    $this->messageManager->addSuccess(
                        __('Sub Account was successfully created.')
                    );
                    $returnToEdit = (bool)$subject->getRequest()->getParam('back', false);
                }
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $returnToEdit = true;
            }
        }
        if ($returnToEdit) {
            if ($id) {
                return $this->resultRedirectFactory->create()->setPath(
                    'sellersubaccount/account/edit',
                    [
                        'id'=>$id,
                        '_current' => true,
                        '_secure' => $subject->getRequest()->isSecure()
                    ]
                );
            } else {
                return $this->resultRedirectFactory->create()->setPath(
                    'sellersubaccount/account/edit',
                    [
                        'seller_id'=>$sellerId,
                        '_current' => true,
                        '_secure' => $subject->getRequest()->isSecure()
                    ]
                );
            }
        } else {
            return $this->resultRedirectFactory->create()->setPath(
                'sellersubaccount/account/manage',
                [
                    'seller_id'=>$sellerId,
                    '_current' => true,
                    '_secure' => $subject->getRequest()->isSecure()
                ]
            );
        }
    }

    public function afterValidateData($subject, $result, $postData){
        $subAccountEmail = $postData['sub_account']['email'];
        //validate is a sub account?
        $customer = $this->customerCollectionFactory->create()
            ->addAttributeToSelect('entity_id')
            ->addFieldToFilter('email', $subAccountEmail)
            ->getFirstItem();
        if($customer->getId()){
            $subAccount = $this->subAccountRepository->getByCustomerId($customer->getId());
            if($subAccount->getId() && $subAccount->getSellerId() != $postData['sub_account']['seller_id']){
                throw new \Exception(__('This email is a sub account of other seller(ID %1)', $subAccount->getSellerId())->render());
            }
        }

        //validate is this a seller
        $sellerColl = $this->sellerCollectionFactory->create()
            ->addFieldToSelect(['entity_id', 'seller_code'])
            ->addFieldToFilter('seller_id', $customer->getId())
            ->getFirstItem();
        if($sellerColl->getId()){
            throw new \Exception(__('This Sub account email is a seller(Code %1)', $sellerColl->getData('seller_code'))->render());
        }

        return $result;
    }
}