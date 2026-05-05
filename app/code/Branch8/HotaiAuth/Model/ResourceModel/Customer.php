<?php

namespace Branch8\HotaiAuth\Model\ResourceModel;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\AccountConfirmation;
use Magento\Eav\Model\Entity\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\RelationComposite;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Validator\Exception as ValidatorException;
use Magento\Framework\Validator\Factory;
use Magento\Store\Model\StoreManagerInterface;

class Customer extends \Magento\Customer\Model\ResourceModel\Customer
{
    private $encryptor;
    private $accountConfirmation;

    protected $request;

    public function __construct(
        Context $context,
        Snapshot $entitySnapshot,
        RelationComposite $entityRelationComposite,
        ScopeConfigInterface $scopeConfig,
        Factory $validatorFactory,
        DateTime $dateTime,
        StoreManagerInterface $storeManager,
        \Magento\Framework\App\RequestInterface $request,
        $data = [],
        AccountConfirmation $accountConfirmation = null,
        EncryptorInterface $encryptor = null
    ) {
        $this->request = $request;
        $this->encryptor = $encryptor ?? ObjectManager::getInstance()
            ->get(EncryptorInterface::class);
        $this->accountConfirmation = $accountConfirmation ?: ObjectManager::getInstance()
            ->get(AccountConfirmation::class);
        parent::__construct(
            $context,
            $entitySnapshot,
            $entityRelationComposite,
            $scopeConfig,
            $validatorFactory,
            $dateTime,
            $storeManager,
            $data,
            $accountConfirmation,
            $encryptor
        );
    }

    /**
     * Check customer scope, email and confirmation key before saving
     *
     * @param DataObject|CustomerInterface $customer
     *
     * @return $this
     * @throws AlreadyExistsException
     * @throws ValidatorException
     * @throws NoSuchEntityException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _beforeSave(DataObject $customer)
    {
        /** @var \Magento\Customer\Model\Customer $customer */
        if ($customer->getStoreId() === null) {
            $customer->setStoreId($this->storeManager->getStore()->getId());
        }
        $customer->getGroupId();

        \Magento\Eav\Model\Entity\VersionControl\AbstractEntity::_beforeSave($customer);

        if (!$customer->getEmail()) {
            throw new ValidatorException(__('The customer email is missing. Enter and try again.'));
        }

        $connection = $this->getConnection();
        $bind = ['email' => $customer->getEmail()];

        // New condition check seller platform
        $select = $connection->select()->from(
            $this->getEntityTable(),
            [$this->getEntityIdField()]
        )->where(
            'email = :email'
        )->where(
            'platform is null'
        );

        if ($customer->getSharingConfig()->isWebsiteScope()) {
            $bind['website_id'] = (int)$customer->getWebsiteId();
            $select->where('website_id = :website_id');
        }
        if ($customer->getId()) {
            $bind['entity_id'] = (int)$customer->getId();
            $select->where('entity_id != :entity_id');
        }

        // New condition check seller platform
        $result = $connection->fetchOne($select, $bind);
        if ($result) {
            throw new AlreadyExistsException(
                __('A customer with the same email address already exists in an associated website.')
            );
        }

        // set confirmation key logic
        if ($this->isConfirmationRequired($customer)) {
            $customer->setConfirmation($customer->getRandomConfirmationKey());
        }
        // remove customer confirmation key from database, if empty
        if (!$customer->getConfirmation()) {
            $customer->setConfirmation(null);
        }

        if (!$customer->getData('ignore_validation_flag')) {
            $this->_validate($customer);
        }

        if ($customer->getData('rp_token')) {
            $rpToken = $customer->getData('rp_token');
            $customer->setRpToken($this->encryptor->encrypt($rpToken));
        }

        return $this;
    }

    /**
     * Checks if customer email verification is required
     *
     * @param DataObject|CustomerInterface $customer
     * @return bool
     */
    private function isConfirmationRequired(DataObject $customer): bool
    {
        return $this->isNewCustomerConfirmationRequired($customer)
            || $this->isExistingCustomerConfirmationRequired($customer);
    }

    /**
     * Checks if customer email verification is required for a new customer
     *
     * @param DataObject|CustomerInterface $customer
     * @return bool
     */
    private function isNewCustomerConfirmationRequired(DataObject $customer): bool
    {
        return !$customer->getId()
            && $this->accountConfirmation->isConfirmationRequired(
                $customer->getWebsiteId(),
                $customer->getId(),
                $customer->getEmail()
            );
    }

    /**
     * Checks if customer email verification is required for an existing customer
     *
     * @param DataObject|CustomerInterface $customer
     * @return bool
     */
    private function isExistingCustomerConfirmationRequired(DataObject $customer): bool
    {
        return $customer->getId()
            && $customer->dataHasChangedFor('email')
            && $this->accountConfirmation->isEmailChangedConfirmationRequired(
                (int)$customer->getWebsiteId(),
                (int)$customer->getId(),
                $customer->getEmail()
            );
    }

    /**
     * Load customer by email
     *
     * @param \Magento\Customer\Model\Customer $customer
     * @param string $email
     * @return $this
     * @throws LocalizedException
     */
    public function loadByEmail(\Magento\Customer\Model\Customer $customer, $email)
    {
        $connection = $this->getConnection();
        $bind = ['customer_email' => $email];
        //if load buy email field but this is seller
        if(strpos($email, \Branch8\Customer\Helper\Data::BUYER_EMAIL_PREFIX) === 0) {
            $select = $connection->select()->from(
                $this->getEntityTable(),
                [$this->getEntityIdField()]
            )->where(
                'buyer_email = :customer_email'
            );
        }else {
            $select = $connection->select()->from(
                $this->getEntityTable(),
                [$this->getEntityIdField()]
            )->where(
                'email = :customer_email'
            );
        }

        if ($customer->getSharingConfig()->isWebsiteScope()) {
            if (!$customer->hasData('website_id')) {
                throw new LocalizedException(
                    __("A customer website ID wasn't specified. The ID must be specified to use the website scope.")
                );
            }
            $bind['website_id'] = (int)$customer->getWebsiteId();
            $select->where('website_id = :website_id');
        }

        $customerId = $connection->fetchOne($select, $bind);
        if ($customerId) {
            $this->load($customer, $customerId);
        }else{
            $customer->setData([]);
        }

        return $this;
    }
}
