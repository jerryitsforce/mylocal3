<?php

namespace Branch8\Customer\Model;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Data\CustomerSecureFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class CustomerRegistry extends \Magento\Customer\Model\CustomerRegistry
{
    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var CustomerSecureFactory
     */
    private $customerSecureFactory;

    /**
     * @var array
     */
    private $customerRegistryById = [];

    /**
     * @var array
     */
    private $customerRegistryByEmail = [];

    /**
     * @var array
     */
    private $customerSecureRegistryById = [];

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * Constructor
     *
     * @param CustomerFactory $customerFactory
     * @param CustomerSecureFactory $customerSecureFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CustomerFactory $customerFactory,
        CustomerSecureFactory $customerSecureFactory,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($customerFactory, $customerSecureFactory, $storeManager);
        $this->customerFactory = $customerFactory;
        $this->customerSecureFactory = $customerSecureFactory;
        $this->storeManager = $storeManager;
    }

    public function retrieve($customerId)
    {
        if (isset($this->customerRegistryById[$customerId])) {
            return $this->customerRegistryById[$customerId];
        }
        /** @var Customer $customer */
        $customer = $this->customerFactory->create()->load($customerId);
        if (!$customer->getId()) {
            // customer does not exist
            throw NoSuchEntityException::singleField('customerId', $customerId);
        } else {
            $emailKey = $this->getEmailKey($customer->getEmail(), $customer->getWebsiteId());
            $this->customerRegistryById[$customerId] = $customer;
            //because the email is not unique(seller and buyer can be same email but != ID)
//            $this->customerRegistryByEmail[$emailKey] = $customer;
            return $customer;
        }
    }

    public function retrieveByEmail($customerEmail, $websiteId = null)
    {
        if ($websiteId === null) {
            $websiteId = $this->storeManager->getStore()->getWebsiteId()
                ?: $this->storeManager->getDefaultStoreView()->getWebsiteId();
        }
//because the email is not unique(seller and buyer can be same email but != ID)
//        $emailKey = $this->getEmailKey($customerEmail, $websiteId);
//        if (isset($this->customerRegistryByEmail[$emailKey])) {
//            return $this->customerRegistryByEmail[$emailKey];
//        }

        /** @var Customer $customer */
        $customer = $this->customerFactory->create();

        $customer->setWebsiteId($websiteId);

        $customer->loadByEmail($customerEmail);
        if (!$customer->getEmail()) {
            // customer does not exist
            throw new NoSuchEntityException(
                __(
                    'No such entity with %fieldName = %fieldValue, %field2Name = %field2Value',
                    [
                        'fieldName' => 'email',
                        'fieldValue' => $customerEmail,
                        'field2Name' => 'websiteId',
                        'field2Value' => $websiteId
                    ]
                )
            );
        } else {
            $this->customerRegistryById[$customer->getId()] = $customer;
            //because the email is not unique(seller and buyer can be same email but != ID)
//            $this->customerRegistryByEmail[$emailKey] = $customer;
            return $customer;
        }
    }
    public function push(Customer $customer)
    {
        $this->customerRegistryById[$customer->getId()] = $customer;
        //because the email is not unique(seller and buyer can be same email but != ID)
//        $emailKey = $this->getEmailKey($customer->getEmail(), $customer->getWebsiteId());
//        $this->customerRegistryByEmail[$emailKey] = $customer;
        return $this;
    }
}