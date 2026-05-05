<?php

namespace Branch8\Customer\Plugin;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Helper\View as CustomerViewHelper;
use Magento\Customer\Model\AccountConfirmation;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\Data\CustomerSecure;
use Magento\Customer\Model\EmailNotificationInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class EmailNotification
{

    /**
     * Constants for the type of new account email to be sent
     */
    const NEW_ACCOUNT_EMAIL_REGISTERED = 'registered';

    /**
     * Welcome email, when password setting is required
     */
    const NEW_ACCOUNT_EMAIL_REGISTERED_NO_PASSWORD = 'registered_no_password';

    /**
     * Welcome email, when confirmation is enabled
     */
    const NEW_ACCOUNT_EMAIL_CONFIRMATION = 'confirmation';

    /**
     * Confirmation email, when account is confirmed
     */
    const NEW_ACCOUNT_EMAIL_CONFIRMED = 'confirmed';

    /**#@+
     * Configuration paths for email templates and identities
     */
    public const XML_PATH_FORGOT_EMAIL_IDENTITY = 'customer/password/forgot_email_identity';

    public const XML_PATH_RESET_PASSWORD_TEMPLATE = 'customer/password/reset_password_template';

    public const XML_PATH_CHANGE_EMAIL_TEMPLATE = 'customer/account_information/change_email_template';

    public const XML_PATH_CHANGE_EMAIL_AND_PASSWORD_TEMPLATE =
        'customer/account_information/change_email_and_password_template';

    public const XML_PATH_FORGOT_EMAIL_TEMPLATE = 'customer/password/forgot_email_template';

    public const XML_PATH_REMIND_EMAIL_TEMPLATE = 'customer/password/remind_email_template';

    public const XML_PATH_REGISTER_EMAIL_IDENTITY = 'customer/create_account/email_identity';

    public const XML_PATH_REGISTER_EMAIL_TEMPLATE = 'customer/create_account/email_template';

    public const XML_PATH_REGISTER_NO_PASSWORD_EMAIL_TEMPLATE = 'customer/create_account/email_no_password_template';

    public const XML_PATH_CONFIRM_EMAIL_TEMPLATE = 'customer/create_account/email_confirmation_template';

    public const XML_PATH_CONFIRMED_EMAIL_TEMPLATE = 'customer/create_account/email_confirmed_template';

    public const XML_PATH_SELLER_FORGOT_PASSWORD_EMAIL_TEMPLATE = 'marketplace/email/seller_forgot_password_template';

    /**
     * self::NEW_ACCOUNT_EMAIL_REGISTERED               welcome email, when confirmation is disabled
     *                                                  and password is set
     * self::NEW_ACCOUNT_EMAIL_REGISTERED_NO_PASSWORD   welcome email, when confirmation is disabled
     *                                                  and password is not set
     * self::NEW_ACCOUNT_EMAIL_CONFIRMED                welcome email, when confirmation is enabled
     *                                                  and password is set
     * self::NEW_ACCOUNT_EMAIL_CONFIRMATION             email with confirmation link
     */
    public const TEMPLATE_TYPES = [
        self::NEW_ACCOUNT_EMAIL_REGISTERED => self::XML_PATH_REGISTER_EMAIL_TEMPLATE,
        self::NEW_ACCOUNT_EMAIL_REGISTERED_NO_PASSWORD => self::XML_PATH_REGISTER_NO_PASSWORD_EMAIL_TEMPLATE,
        self::NEW_ACCOUNT_EMAIL_CONFIRMED => self::XML_PATH_CONFIRMED_EMAIL_TEMPLATE,
        self::NEW_ACCOUNT_EMAIL_CONFIRMATION => self::XML_PATH_CONFIRM_EMAIL_TEMPLATE,
    ];



    /**#@-*/

    /**
     * @var CustomerRegistry
     */
    private $customerRegistry;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var CustomerViewHelper
     */
    protected $customerViewHelper;

    /**
     * @var DataObjectProcessor
     */
    protected $dataProcessor;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var SenderResolverInterface
     */
    private $senderResolver;

    /**
     * @var Emulation
     */
    private $emulation;

    /**
     * @var AccountConfirmation
     */
    private AccountConfirmation $accountConfirmation;
    /**
     * @var \Webkul\SellerSubAccount\Helper\Data
     */
    protected $subAccountHelper;
    /**
     * @var \Branch8\MarketplaceSubAccount\Helper\Data
     */
    protected $b8SubAccountHelper;

    /**
     * @param CustomerRegistry $customerRegistry
     * @param StoreManagerInterface $storeManager
     * @param TransportBuilder $transportBuilder
     * @param CustomerViewHelper $customerViewHelper
     * @param DataObjectProcessor $dataProcessor
     * @param ScopeConfigInterface $scopeConfig
     * @param \Webkul\SellerSubAccount\Helper\Data $subAccountHelper
     * @param \Branch8\MarketplaceSubAccount\Helper\Data $b8SubAccountHelper
     * @param SenderResolverInterface|null $senderResolver
     * @param Emulation|null $emulation
     * @param AccountConfirmation|null $accountConfirmation
     */
    public function __construct(
        CustomerRegistry $customerRegistry,
        StoreManagerInterface $storeManager,
        TransportBuilder $transportBuilder,
        CustomerViewHelper $customerViewHelper,
        DataObjectProcessor $dataProcessor,
        ScopeConfigInterface $scopeConfig,
        \Webkul\SellerSubAccount\Helper\Data $subAccountHelper,
        \Branch8\MarketplaceSubAccount\Helper\Data $b8SubAccountHelper,
        SenderResolverInterface $senderResolver = null,
        Emulation $emulation = null,
        ?AccountConfirmation $accountConfirmation = null
    ) {
        $this->customerRegistry = $customerRegistry;
        $this->storeManager = $storeManager;
        $this->transportBuilder = $transportBuilder;
        $this->customerViewHelper = $customerViewHelper;
        $this->dataProcessor = $dataProcessor;
        $this->scopeConfig = $scopeConfig;
        $this->senderResolver = $senderResolver ?? ObjectManager::getInstance()->get(SenderResolverInterface::class);
        $this->emulation = $emulation ?? ObjectManager::getInstance()->get(Emulation::class);
        $this->accountConfirmation = $accountConfirmation ?? ObjectManager::getInstance()
            ->get(AccountConfirmation::class);
        $this->subAccountHelper = $subAccountHelper;
        $this->b8SubAccountHelper = $b8SubAccountHelper;
    }



    /**
     * Send corresponding email template
     *
     * @param CustomerInterface $customer
     * @param string $template configuration path of email template
     * @param string $sender configuration path of email identity
     * @param array $templateParams
     * @param int|null $storeId
     * @param string $email
     * @return void
     * @throws MailException|LocalizedException
     */
    private function sendEmailTemplate(
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

    /**
     * Create an object with data merged from Customer and CustomerSecure
     *
     * @param CustomerInterface $customer
     * @return CustomerSecure
     * @throws NoSuchEntityException
     */
    private function getFullCustomerObject($customer): CustomerSecure
    {
        // No need to flatten the custom attributes or nested objects since the only usage is for email templates and
        // object passed for events
        $mergedCustomerData = $this->customerRegistry->retrieveSecureData($customer->getId());
        $customerData = $this->dataProcessor
            ->buildOutputDataArray($customer, CustomerInterface::class);
        $mergedCustomerData->addData($customerData);
        $mergedCustomerData->setData('name', $this->customerViewHelper->getCustomerName($customer));
        return $mergedCustomerData;
    }

    /**
     * Get either first store ID from a set website or the provided as default
     *
     * @param CustomerInterface $customer
     * @param int|string|null $defaultStoreId
     * @return int
     * @throws LocalizedException
     */
    private function getWebsiteStoreId($customer, $defaultStoreId = null): int
    {
        if ($customer->getWebsiteId() != 0 && empty($defaultStoreId)) {
            $storeIds = $this->storeManager->getWebsite($customer->getWebsiteId())->getStoreIds();
            $defaultStoreId = reset($storeIds);
        }
        return $defaultStoreId;
    }


    /**
     * Send email with new account related information
     *
     * @param CustomerInterface $customer
     * @param string $type
     * @param string $backUrl
     * @param int|null $storeId
     * @param string $sendemailStoreId
     * @return void
     * @throws LocalizedException
     */
    public function aroundNewAccount(
        $subject,
        $process,
        CustomerInterface $customer,
                          $type = self::NEW_ACCOUNT_EMAIL_REGISTERED,
                          $backUrl = '',
                          $storeId = null,
                          $sendemailStoreId = null
    ): void {
        /**
         * Now, No need to send new account mail for buyer, seller, subaccount
         * Note: $customer->getCustomAttribute('platform')->getValue() 
         * If you need to send emal, need to be check if($customer->getCustomAttribute('platform'))
         * Because Amasty Warm up(cache) will create new account without platform attribute
         */
        return;
        $types = self::TEMPLATE_TYPES;
        //get account type to detect seller/ sub account/ buyer

        $platform = $customer->getCustomAttribute('platform')->getValue();
        if($platform != 'seller'){
            /**
             * $platform != 'seller': this is buyer account
             */
            /**
             * Current login: not send welcome email for buyer
             */
//            $process($customer, $type, $backUrl, $storeId, $sendemailStoreId);
        }else{
            /**
             * Do not send mail here for seller, sub account.
             * Seller new email already send by WK extension
             */

        }

    }
    public function aroundPasswordResetConfirmation($subject, $process, CustomerInterface $customer): void
    {
        $platform = $customer->getCustomAttribute('platform')->getValue();
        if($platform == 'seller'){
            $storeId = $customer->getStoreId();
            if ($storeId === null) {
                $storeId = $this->getWebsiteStoreId($customer);
            }

            $customerEmailData = $this->getFullCustomerObject($customer);

            $this->sendEmailTemplate(
                $customer,
                self::XML_PATH_SELLER_FORGOT_PASSWORD_EMAIL_TEMPLATE,
                self::XML_PATH_FORGOT_EMAIL_IDENTITY,
                ['customer' => $customerEmailData, 'store' => $this->storeManager->getStore($storeId)],
                $storeId
            );
        }

    }

    public function aroundCredentialsChanged($subject, $process, CustomerInterface $savedCustomer,
                                                                                   $origCustomerEmail,
                                                                                   $isPasswordChanged = false)
    {
        //no send mail here
        //only Sub account need to send email change, it will send on controller
    }

}