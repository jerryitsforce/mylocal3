<?php

namespace Branch8\MarketplaceSubAccount\Helper;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Helper\View as CustomerViewHelper;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\Data\CustomerSecure;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Math\Random;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Webkul\SellerSubAccount\Api\SubAccountRepositoryInterface;
use Magento\Customer\Model\EmailNotificationInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    public const XML_PATH_SUB_ACCOUNT_REGISTER_NO_PASSWORD_EMAIL_TEMPLATE = 'sellersubaccount/email_setting/new_sub_email_without_password';
    public const SUB_ACCOUNT_TEMPLATE_TYPES = [
        EmailNotificationInterface::NEW_ACCOUNT_EMAIL_REGISTERED_NO_PASSWORD => self::XML_PATH_SUB_ACCOUNT_REGISTER_NO_PASSWORD_EMAIL_TEMPLATE
    ];

    public const XML_PATH_ENABLE_OLD_SUB_ACCOUNT = 'sellersubaccount/email_setting/enable_sub_account_before_changed';

    public const XML_PATH_EMAIL_OLD_SUB_ACCOUNT_TEMPLATE = 'sellersubaccount/email_setting/sub_account_before_changed';
    /**
     * @var SubAccountRepositoryInterface
     */
    protected $_subAccountRepository;
    /**
     * @var CustomerRepositoryInterface
     */
    protected $_customerRepository;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var CustomerRegistry
     */
    protected $_customerRegistry;
    /**
     * @var DataObjectProcessor
     */
    protected $dataProcessor;
    /**
     * @var CustomerViewHelper
     */
    protected $customerViewHelper;
    /**
     * @var SenderResolverInterface
     */
    protected $senderResolver;
    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;
    /**
     * @var Emulation
     */
    protected $emulation;
    /**
     * @var Random
     */
    protected $mathRandom;
    /**
     * @var \Magento\Customer\Model\AccountManagement
     */
    protected $accountManagement;
    /**
     * @var \Webkul\SellerSubAccount\Model\ResourceModel\SubAccount\CollectionFactory
     */
    protected $subAccountCollectionFactory;
    /**
     * @var \Branch8\Marketplace\Helper\Email
     */
    protected $branch8MpEmail;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param SubAccountRepositoryInterface $subAccountRepository
     * @param CustomerRepositoryInterface $customerRepository
     * @param StoreManagerInterface $storeManager
     * @param CustomerRegistry $customerRegistry
     * @param DataObjectProcessor $dataProcessor
     * @param CustomerViewHelper $customerViewHelper
     * @param SenderResolverInterface $senderResolver
     * @param TransportBuilder $transportBuilder
     * @param Emulation $emulation
     * @param Random $mathRandom
     * @param \Magento\Customer\Model\AccountManagement $accountManagement
     * @param \Webkul\SellerSubAccount\Model\ResourceModel\SubAccount\CollectionFactory $subAccountCollectionFactory
     * @param \Branch8\Marketplace\Helper\Email $branch8MpEmail
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        SubAccountRepositoryInterface         $subAccountRepository,
        CustomerRepositoryInterface           $customerRepository,
        StoreManagerInterface                 $storeManager,
        CustomerRegistry $customerRegistry,
        DataObjectProcessor $dataProcessor,
        CustomerViewHelper $customerViewHelper,
        SenderResolverInterface $senderResolver,
        TransportBuilder $transportBuilder,
        Emulation $emulation,
        Random $mathRandom,
        \Magento\Customer\Model\AccountManagement $accountManagement,
        \Webkul\SellerSubAccount\Model\ResourceModel\SubAccount\CollectionFactory $subAccountCollectionFactory,
        \Branch8\Marketplace\Helper\Email $branch8MpEmail
    )
    {
        parent::__construct($context);
        $this->_subAccountRepository = $subAccountRepository;
        $this->_customerRepository = $customerRepository;
        $this->storeManager = $storeManager;
        $this->_customerRegistry = $customerRegistry;
        $this->dataProcessor = $dataProcessor;
        $this->customerViewHelper = $customerViewHelper;
        $this->senderResolver = $senderResolver;
        $this->transportBuilder = $transportBuilder;
        $this->emulation = $emulation;
        $this->mathRandom = $mathRandom;
        $this->accountManagement = $accountManagement;
        $this->subAccountCollectionFactory = $subAccountCollectionFactory;
        $this->branch8MpEmail = $branch8MpEmail;
    }

    public function sendMailNewSubAccount($customerId, $type, $storeId = null, $sendemailStoreId = null)
    {
        $subAccount = $this->_subAccountRepository->getByCustomerId($customerId);
        /**
         * is Sub account
         */
        if ($subAccount->getId()) {
            $subAccountTypes = self::SUB_ACCOUNT_TEMPLATE_TYPES;
            if (!isset($subAccountTypes[$type])) {
                throw new LocalizedException(
                    __('The transactional account email type is incorrect. Verify and try again.')
                );
            }

            $customer = $this->_customerRepository->getById($customerId);
            $customerSecure = $this->_customerRegistry->retrieveSecureData($customer->getId());
            $rpToken = $customerSecure->getRpToken();
            if(!$rpToken){
                $newLinkToken = $this->mathRandom->getUniqueHash();
                $this->accountManagement->changeResetPasswordLinkToken($customer, $newLinkToken);
            }

            if ($storeId === null) {
                $storeId = $this->getWebsiteStoreId($customer, $sendemailStoreId);
            }

            $store = $this->storeManager->getStore($customer->getStoreId());

            $customerEmailData = $this->getFullCustomerObject($customer);

            $this->sendEmailTemplate(
                $customer,
                $subAccountTypes[$type],
                \Magento\Customer\Model\EmailNotification::XML_PATH_REGISTER_EMAIL_IDENTITY,
                ['customer' => $customerEmailData, 'store' => $store],
                $storeId
            );
        }


    }

    public function sendMailToOldSubAccount($customer, $storeId = null, $sendemailStoreId = null)
    {
        if($this->scopeConfig->getValue(self::XML_PATH_ENABLE_OLD_SUB_ACCOUNT)){
            if ($storeId === null) {
                $storeId = $this->getWebsiteStoreId($customer, $sendemailStoreId);
            }
            $store = $this->storeManager->getStore($customer->getStoreId());
            $this->sendEmailTemplate(
                $customer,
                self::XML_PATH_EMAIL_OLD_SUB_ACCOUNT_TEMPLATE,
                \Magento\Customer\Model\EmailNotification::XML_PATH_REGISTER_EMAIL_IDENTITY,
                ['customer' => $this->customerViewHelper->getCustomerName($customer), 'store' => $store],
                $storeId
            );
        }
    }

    private function getWebsiteStoreId($customer, $defaultStoreId = null): int
    {
        if ($customer->getWebsiteId() != 0 && empty($defaultStoreId)) {
            $storeIds = $this->storeManager->getWebsite($customer->getWebsiteId())->getStoreIds();
            $defaultStoreId = reset($storeIds);
        }
        return $defaultStoreId;
    }

    private function getFullCustomerObject($customer): CustomerSecure
    {
        // No need to flatten the custom attributes or nested objects since the only usage is for email templates and
        // object passed for events
        $mergedCustomerData = $this->_customerRegistry->retrieveSecureData($customer->getId());
        $customerData = $this->dataProcessor
            ->buildOutputDataArray($customer, CustomerInterface::class);
        $mergedCustomerData->addData($customerData);
        $mergedCustomerData->setData('name', $this->customerViewHelper->getCustomerName($customer));
        return $mergedCustomerData;
    }
    public function sendEmailTemplate(
        $customer,
        $template,
        $sender,
        $templateParams = [],
        $storeId = null,
        $email = null
    ): void {
        $templateId = $this->scopeConfig->getValue($template, ScopeInterface::SCOPE_STORE, $storeId);
        if ($email === null) {
            $email = $customer->getEmail();
        }

        /** @var array $from */
        $from = $this->senderResolver->resolve(
            $this->scopeConfig->getValue($sender, ScopeInterface::SCOPE_STORE, $storeId),
            $storeId
        );

        $transport = $this->transportBuilder->setTemplateIdentifier($templateId)
            ->setTemplateOptions(['area' => 'frontend', 'store' => $storeId])
            ->setTemplateVars($templateParams)
            ->setFrom($from)
            ->addTo($email, $this->customerViewHelper->getCustomerName($customer))
            ->getTransport();

        $this->emulation->startEnvironmentEmulation($storeId, \Magento\Framework\App\Area::AREA_FRONTEND);
        $transport->sendMessage();
        $this->emulation->stopEnvironmentEmulation();
    }


    public function sendProductStatusMailToSubAccount($sellerId, $emailTemplateVariables, $senderInfo){
        $subAccountCol = $this->subAccountCollectionFactory->create()
            ->addFieldToFilter('seller_id', $sellerId);
        $select = $subAccountCol->getSelect();
        $select->joinLeft(['ce' => 'customer_entity'], 'ce.entity_id = main_table.customer_id', ['email', 'firstname', 'lastname']);

        foreach($subAccountCol as $_sub){
            $permission = $_sub->getPermissionType();
            $permissionArr = explode(',', (string)$permission);
            if(in_array('marketplace/product/add', $permissionArr)){
                $subAccountName = sprintf('%s %s', $_sub->getFirstname(), $_sub->getLastname());
                $subAccountEmailData = ['name' => $subAccountName, 'email' => $_sub->getEmail()];
                $this->branch8MpEmail->sendProductStatusMail($emailTemplateVariables, $senderInfo, $subAccountEmailData);
            }
        }
    }

    public function sendProductUnapproveMailToSubAccount($sellerId, $emailTemplateVariables, $senderInfo)
    {
        $subAccountCol = $this->subAccountCollectionFactory->create()
            ->addFieldToFilter('seller_id', $sellerId);
        $select = $subAccountCol->getSelect();
        $select->joinLeft(['ce' => 'customer_entity'], 'ce.entity_id = main_table.customer_id', ['email', 'firstname', 'lastname']);

        foreach($subAccountCol as $_sub){
            $permission = $_sub->getPermissionType();
            $permissionArr = explode(',', (string)$permission);
            if(in_array('marketplace/product/add', $permissionArr)){
                $subAccountName = sprintf('%s %s', $_sub->getFirstname(), $_sub->getLastname());
                $subAccountEmailData = ['name' => $subAccountName, 'email' => $_sub->getEmail()];
                $this->branch8MpEmail->sendProductUnapproveMail($emailTemplateVariables, $senderInfo, $subAccountEmailData);
            }
        }
    }

    public function sendProductDenyMailToSubAccount($sellerId, $emailTemplateVariables, $senderInfo)
    {
        $subAccountCol = $this->subAccountCollectionFactory->create()
            ->addFieldToFilter('seller_id', $sellerId);
        $select = $subAccountCol->getSelect();
        $select->joinLeft(['ce' => 'customer_entity'], 'ce.entity_id = main_table.customer_id', ['email', 'firstname', 'lastname']);

        foreach($subAccountCol as $_sub){
            $permission = $_sub->getPermissionType();
            $permissionArr = explode(',', (string)$permission);
            if(in_array('marketplace/product/add', $permissionArr)){
                $subAccountName = sprintf('%s %s', $_sub->getFirstname(), $_sub->getLastname());
                $subAccountEmailData = ['name' => $subAccountName, 'email' => $_sub->getEmail()];
                $this->branch8MpEmail->sendProductDenyMail($emailTemplateVariables, $senderInfo, $subAccountEmailData);
            }
        }
    }

}