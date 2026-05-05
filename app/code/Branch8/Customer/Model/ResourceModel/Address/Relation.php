<?php

declare(strict_types=1);

namespace Branch8\Customer\Model\ResourceModel\Address;

use Branch8\OneStepCheckout\Model\Source\HotaiAddressType;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Address;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\RelationInterface;

/**
 * Class represents save operations for customer address relations
 */
class Relation implements RelationInterface
{
    const DEFAULT_CONVENIENCE_STORE = 'default_convenience_store';

    /**
     * @var CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var CustomerRegistry
     */
    private $customerRegistry;

    /**
     * @param CustomerFactory $customerFactory
     * @param CustomerRegistry $customerRegistry
     */
    public function __construct(
        CustomerFactory $customerFactory,
        CustomerRegistry $customerRegistry
    ) {
        $this->customerFactory = $customerFactory;
        $this->customerRegistry = $customerRegistry;
    }

    /**
     * Process object relations
     *
     * @param AbstractModel $object
     * @return void
     */
    public function processRelation(AbstractModel $object): void
    {
        /** @var $object Address */
        if (!$object->getIsCustomerSaveTransaction() && $object->getId()) {
            $customer = $this->customerFactory->create()->load($object->getCustomerId());

            $changedAddresses = [];
            $changedAddresses = $this->getDefaultBillingChangedAddress($object, $customer, $changedAddresses);
            $changedAddresses = $this->getDefaultShippingChangedAddress($object, $customer, $changedAddresses);
            $changedAddresses = $this->getDefaultConvenienceChangedAddress($object, $customer, $changedAddresses);

            if ($changedAddresses) {
                $customer->getResource()->getConnection()->update(
                    $customer->getResource()->getTable('customer_entity'),
                    $changedAddresses,
                    $customer->getResource()->getConnection()->quoteInto('entity_id = ?', $customer->getId())
                );
                $this->updateCustomerRegistry($customer, $changedAddresses);
            }
        }
    }

    /**
     * Get default billing changed address
     *
     * @param Address $object
     * @param Customer $customer
     * @param array $changedAddresses
     * @return array
     */
    private function getDefaultBillingChangedAddress(
        Address $object,
        Customer $customer,
        array $changedAddresses
    ): array {
        if ($object->getIsDefaultBilling()) {
            $changedAddresses[CustomerInterface::DEFAULT_BILLING] = $object->getId();
        } elseif ($customer->getDefaultBillingAddress()
            && $object->getIsDefaultBilling() === false
            && (int)$customer->getDefaultBillingAddress()->getId() === (int)$object->getId()
        ) {
            $changedAddresses[CustomerInterface::DEFAULT_BILLING] = null;
        }

        return $changedAddresses;
    }

    /**
     * Get default shipping changed address
     *
     * @param Address $object
     * @param Customer $customer
     * @param array $changedAddresses
     * @return array
     */
    private function getDefaultShippingChangedAddress(
        Address $object,
        Customer $customer,
        array $changedAddresses
    ): array {
        if ($object->getIsDefaultShipping()) {
            $changedAddresses[CustomerInterface::DEFAULT_SHIPPING] = $object->getId();
        } elseif ($customer->getDefaultShippingAddress()
            && $object->getIsDefaultShipping() === false
            && (int)$customer->getDefaultShippingAddress()->getId() === (int)$object->getId()
        ) {
            $changedAddresses[CustomerInterface::DEFAULT_SHIPPING] = null;
        }

        return $changedAddresses;
    }

    /**
     * Get default convenience changed address
     *
     * @param Address $object
     * @param Customer $customer
     * @param array $changedAddresses
     * @return array
     */
    private function getDefaultConvenienceChangedAddress(
        Address $object,
        Customer $customer,
        array $changedAddresses
    ): array {
        if($object->getData('hotai_address_type') !== HotaiAddressType::ADDRESS_TYPE_CONVENIENCE_STORE) {
            return $changedAddresses;
        }

        if ($this->isDefaultConvenienceStore($object)) {
            //set customer_entity.default_convenience_store = address_id
            $changedAddresses[self::DEFAULT_CONVENIENCE_STORE] = $object->getId();
        } elseif ($customer->getData(self::DEFAULT_CONVENIENCE_STORE)
            && !$object->getData(self::DEFAULT_CONVENIENCE_STORE)
            && (int)$this->getCustomerDefaultConvenienceStore($customer) === (int)$object->getId()
        ) {
            //set customer_entity.default_convenience_store = null
            $changedAddresses[self::DEFAULT_CONVENIENCE_STORE] = null;
        }

        return $changedAddresses;
    }


    public function isDefaultConvenienceStore($address)
    {
        $isDefault = false;
        $extensionAttributes = $address->getExtensionAttributes();
        if ($extensionAttributes instanceof \Magento\Customer\Api\Data\AddressExtensionInterface) {
            $isDefault = $extensionAttributes->getIsDefaultConvenienceStore();
        }

        if(is_array($extensionAttributes)) {
            $isDefault = $extensionAttributes['is_default_convenience_store'];
        }

        return filter_var($isDefault, FILTER_VALIDATE_BOOLEAN);
    }

    public function getCustomerDefaultConvenienceStore(Customer $customer)
    {
        $primaryAddress = $customer->getAddressesCollection()->getItemById($customer->getData(self::DEFAULT_CONVENIENCE_STORE));
        return $primaryAddress ? $primaryAddress->getId() : false;

    }

    /**
     * Push updated customer entity to the registry.
     *
     * @param Customer $customer
     * @param array $changedAddresses
     * @return void
     */
    private function updateCustomerRegistry(Customer $customer, array $changedAddresses): void
    {
        if (array_key_exists(CustomerInterface::DEFAULT_BILLING, $changedAddresses)) {
            $customer->setDefaultBilling($changedAddresses[CustomerInterface::DEFAULT_BILLING]);
        }

        if (array_key_exists(CustomerInterface::DEFAULT_SHIPPING, $changedAddresses)) {
            $customer->setDefaultShipping($changedAddresses[CustomerInterface::DEFAULT_SHIPPING]);
        }

        if (array_key_exists(self::DEFAULT_CONVENIENCE_STORE, $changedAddresses)) {
            $customer->setData(self::DEFAULT_CONVENIENCE_STORE, $changedAddresses[self::DEFAULT_CONVENIENCE_STORE]);
        }

        $this->customerRegistry->push($customer);
    }

    /**
     * Checks if address has chosen as default and has had an id
     *
     * @deprecated 102.0.1 Is not used anymore due to changes in logic of save of address.
     *             If address was default and becomes not default than default address id for customer must be
     *             set to null
     * @param AbstractModel $object
     * @return bool
     */
    protected function isAddressDefault(AbstractModel $object)
    {
        return $object->getId() && ($object->getIsDefaultBilling() || $object->getIsDefaultShipping());
    }
}
