<?php

namespace Branch8\Customer\Model\ResourceModel;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerSearchResultsInterfaceFactory;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\Customer as CustomerModel;
use Magento\Customer\Model\Customer\NotificationStorage;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\Data\CustomerSecureFactory;
use Magento\Customer\Model\Delegation\Data\NewOperation;
use Magento\Customer\Model\Delegation\Storage as DelegatedStorage;
use Magento\Customer\Model\ResourceModel\AddressRepository;
use Magento\Customer\Model\ResourceModel\Customer;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\ImageProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class CustomerRepository extends \Magento\Customer\Model\ResourceModel\CustomerRepository{

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var NotificationStorage
     */
    private $notificationStorage;

    /**
     * @var DelegatedStorage
     */
    private $delegatedStorage;

    /**
     * @var GroupRepositoryInterface
     */
    private $groupRepository;
    /**
     * @var Customer\CollectionFactory
     */
    protected $customerCollectionFactory;
    /**
     * @var \Branch8\Customer\Helper\GenerateBuyerEmail
     */
    protected $generateBuyerEmail;

    protected $resourceConnection;

    /**
     * @param CustomerFactory $customerFactory
     * @param CustomerSecureFactory $customerSecureFactory
     * @param CustomerRegistry $customerRegistry
     * @param AddressRepository $addressRepository
     * @param Customer $customerResourceModel
     * @param CustomerMetadataInterface $customerMetadata
     * @param CustomerSearchResultsInterfaceFactory $searchResultsFactory
     * @param ManagerInterface $eventManager
     * @param StoreManagerInterface $storeManager
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     * @param DataObjectHelper $dataObjectHelper
     * @param ImageProcessorInterface $imageProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param CollectionProcessorInterface $collectionProcessor
     * @param NotificationStorage $notificationStorage
     * @param Customer\CollectionFactory $customerCollectionFactory
     * @param \Branch8\Customer\Helper\GenerateBuyerEmail $generateBuyerEmail
     * @param DelegatedStorage|null $delegatedStorage
     * @param GroupRepositoryInterface|null $groupRepository
     */
    public function __construct(
        CustomerFactory $customerFactory,
        CustomerSecureFactory $customerSecureFactory,
        CustomerRegistry $customerRegistry,
        AddressRepository $addressRepository,
        Customer $customerResourceModel,
        CustomerMetadataInterface $customerMetadata,
        CustomerSearchResultsInterfaceFactory $searchResultsFactory,
        ManagerInterface $eventManager,
        StoreManagerInterface $storeManager,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        DataObjectHelper $dataObjectHelper,
        ImageProcessorInterface $imageProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        CollectionProcessorInterface $collectionProcessor,
        NotificationStorage $notificationStorage,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory,
        \Branch8\Customer\Helper\GenerateBuyerEmail $generateBuyerEmail,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        DelegatedStorage $delegatedStorage = null,
        ?GroupRepositoryInterface $groupRepository = null
    ) {
        parent::__construct($customerFactory, $customerSecureFactory, $customerRegistry, $addressRepository,
            $customerResourceModel, $customerMetadata, $searchResultsFactory, $eventManager, $storeManager,
            $extensibleDataObjectConverter, $dataObjectHelper, $imageProcessor, $extensionAttributesJoinProcessor, $collectionProcessor,
            $notificationStorage, $delegatedStorage, $groupRepository);

        $this->collectionProcessor = $collectionProcessor;
        $this->notificationStorage = $notificationStorage;
        $this->delegatedStorage = $delegatedStorage ?? ObjectManager::getInstance()->get(DelegatedStorage::class);
        $this->groupRepository = $groupRepository ?: ObjectManager::getInstance()->get(GroupRepositoryInterface::class);
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->generateBuyerEmail = $generateBuyerEmail;
        $this->resourceConnection = $resourceConnection;
    }
    public function save(CustomerInterface $customer, $passwordHash = null)
    {
        //check member_seq unique
        $this->validateMemberSeq($customer);
        if(!$customer->getId()){
            //set fake email for buyer
            if($customer->getCustomAttribute('platform') && $customer->getCustomAttribute('platform')->getValue() != 'seller'){
                $customer->setEmail($this->generateBuyerEmail->generateBuyerEmail());
            }
        }

        /** @var NewOperation|null $delegatedNewOperation */
        $delegatedNewOperation = !$customer->getId() ? $this->delegatedStorage->consumeNewOperation() : null;
        $prevCustomerData = $prevCustomerDataArr = null;
        if ($customer->getDefaultBilling()) {
            $this->validateDefaultAddress($customer, CustomerInterface::DEFAULT_BILLING);
        }
        if ($customer->getDefaultShipping()) {
            $this->validateDefaultAddress($customer, CustomerInterface::DEFAULT_SHIPPING);
        }
        if ($customer->getId()) {
            $prevCustomerData = $this->getById($customer->getId());


            $prevCustomerDataArray = $prevCustomerData->__toArray();
            /**
             * API create
             */
            $prevCustomerDataArray['email'] = $this->generateBuyerEmail->generateBuyerEmail();

            $prevCustomerDataArr = $this->prepareCustomerData($prevCustomerDataArray);
            $customer->setCreatedAt($prevCustomerData->getCreatedAt());
        }
        /** @var $customer \Magento\Customer\Model\Data\Customer */
        $customerArr = $customer->__toArray();
        $customer = $this->imageProcessor->save(
            $customer,
            CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER,
            $prevCustomerData
        );
        $origAddresses = $customer->getAddresses();
        $customer->setAddresses([]);
        $customerData = $this->extensibleDataObjectConverter->toNestedArray($customer, [], CustomerInterface::class);
        $customer->setAddresses($origAddresses);
        /** @var CustomerModel $customerModel */
        $customerModel = $this->customerFactory->create(['data' => $customerData]);
        $this->populateWithOrigData($customerModel, $prevCustomerDataArr);
        //Model's actual ID field maybe different than "id" so "id" field from $customerData may be ignored.
        $customerModel->setId($customer->getId());
        /*
         * Case save BE
         */
        if($customer->getId() && $prevCustomerData->getCustomAttribute('platform') && $prevCustomerData->getCustomAttribute('platform')->getValue() != 'seller'){
            $customerModel->setEmail($prevCustomerDataArr['email']);
        }

        $storeId = $customerModel->getStoreId();
        if ($storeId === null) {
            $customerModel->setStoreId(
                $prevCustomerData ? $prevCustomerData->getStoreId() : $this->storeManager->getStore()->getId()
            );
        }
        $this->validateGroupId((int)$customer->getGroupId());
        $this->setCustomerGroupId($customerModel, $customerArr, $prevCustomerDataArr);
        // Need to use attribute set or future updates can cause data loss
        if (!$customerModel->getAttributeSetId()) {
            $customerModel->setAttributeSetId(CustomerMetadataInterface::ATTRIBUTE_SET_ID_CUSTOMER);
        }
        $this->populateCustomerWithSecureData($customerModel, $passwordHash);
        // If customer email was changed, reset RpToken info
        if ($prevCustomerData && $prevCustomerData->getEmail() !== $customerModel->getEmail()) {
            $customerModel->setRpToken(null);
            $customerModel->setRpTokenCreatedAt(null);
        }
        if (!array_key_exists('addresses', $customerArr)
            && null !== $prevCustomerDataArr
            && array_key_exists('default_billing', $prevCustomerDataArr)
        ) {
            $customerModel->setDefaultBilling($prevCustomerDataArr['default_billing']);
        }
        if (!array_key_exists('addresses', $customerArr)
            && null !== $prevCustomerDataArr
            && array_key_exists('default_shipping', $prevCustomerDataArr)
        ) {
            $customerModel->setDefaultShipping($prevCustomerDataArr['default_shipping']);
        }
        $this->setValidationFlag($customerArr, $customerModel);
        $customerModel->save();
        $this->customerRegistry->push($customerModel);
        $customerId = $customerModel->getId();

        if (!$customer->getAddresses()
            && $delegatedNewOperation
            && $delegatedNewOperation->getCustomer()->getAddresses()
        ) {
            $customer->setAddresses($delegatedNewOperation->getCustomer()->getAddresses());
        }
        if ($customer->getAddresses() !== null && !$customerModel->getData('ignore_validation_flag')) {
            if ($customer->getId()) {
                $existingAddresses = $this->getById($customer->getId())->getAddresses();
                $getIdFunc = function ($address) {
                    return $address->getId();
                };
                $existingAddressIds = array_map($getIdFunc, $existingAddresses);
            } else {
                $existingAddressIds = [];
            }
            $savedAddressIds = [];
            foreach ($customer->getAddresses() as $address) {
                $address->setCustomerId($customerId)
                    ->setRegion($address->getRegion());
                $this->addressRepository->save($address);
                if ($address->getId()) {
                    $savedAddressIds[] = $address->getId();
                }
            }
            $this->deleteAddressesByIds(array_diff($existingAddressIds, $savedAddressIds));
        }
        $this->customerRegistry->remove($customerId);
//        $platformObj = $customerModel->getDataModel()->getCustomAttribute('platform');
//        $platform = '';
//        if($platformObj){
//            $platform = $platformObj->getValue();
//        }
//        if($platform == 'seller'){
//            $email = $customer->getEmail();
//        }else{
//            //buyer
//            $email = $customerModel->getDataModel()->getCustomAttribute('buyer_email')->getValue();
//        }
//        $email = $customer->getEmail();
//        $savedCustomer = $this->get($email, $customer->getWebsiteId());
        $savedCustomer = $this->getById($customerId);
        $this->eventManager->dispatch(
            'customer_save_after_data_object',
            [
                'customer_data_object' => $savedCustomer,
                'orig_customer_data_object' => $prevCustomerData,
                'delegate_data' => $delegatedNewOperation ? $delegatedNewOperation->getAdditionalData() : [],
            ]
        );
        return $customerModel->getDataModel();
    }

    private function getDbEmail($customerId)
    {
        $conn = $this->resourceConnection->getConnection();
        $select = $conn->select()
            ->from(['ce' => 'customer_entity'], ['email'])
            ->where('entity_id='.$customerId);
        return $conn->fetchOne($select);
    }

    public function validateMemberSeq($customer){

        $memberSeqObj = $customer->getCustomAttribute('member_seq');

        if(!$customer->getId()){
            $platform = $customer->getCustomAttribute('platform');
            if((!$memberSeqObj || $memberSeqObj->getValue() == '') && $platform && $platform->getValue() != 'seller'){
                throw new \Magento\Framework\Exception\LocalizedException(__('Member seq is required for buyer account.'));
            }
        }
        if(!$memberSeqObj){
            return;
        }
        //validate member_seq required for

        $memberSeq = $memberSeqObj->getValue();

        $collection = $this->customerCollectionFactory->create()
            ->addAttributeToSelect('entity_id')
            ->addAttributeToSelect('member_seq')
            ->addAttributeToFilter('platform', ['neq' => 'seller'])
            ->addAttributeToFilter('member_seq', $memberSeq);
        if($collection->getSize()){
            $customerId = $customer->getId();
            foreach($collection as $_customer){
                if(!$customerId || ($_customer->getId() != $customer->getId())){
                    throw new \Magento\Framework\Exception\LocalizedException(__('Duplicate member_seq found.'));
                }
            }
        }
    }

    /**
     * Populate customer model with previous data
     *
     * @param CustomerModel $customerModel
     * @param ?array $prevCustomerDataArr
     */
    private function populateWithOrigData(CustomerModel $customerModel, ?array $prevCustomerDataArr)
    {
        if (!empty($prevCustomerDataArr)) {
            foreach ($prevCustomerDataArr as $field => $value) {
                $customerModel->setOrigData($field, $value);
            }
        }
    }

    /**
     * Delete addresses by ids
     *
     * @param array $addressIds
     * @return void
     */
    private function deleteAddressesByIds(array $addressIds): void
    {
        foreach ($addressIds as $id) {
            $this->addressRepository->deleteById($id);
        }
    }

    /**
     * Validate customer group id if exist
     *
     * @param int|null $groupId
     * @return bool
     * @throws LocalizedException
     */
    private function validateGroupId(?int $groupId): bool
    {
        if ($groupId) {
            try {
                $this->groupRepository->getById($groupId);
            } catch (NoSuchEntityException $e) {
                throw new LocalizedException(__('The specified customer group id does not exist.'));
            }
        }

        return true;
    }

    /**
     * Set secure data to customer model
     *
     * @param \Magento\Customer\Model\Customer $customerModel
     * @param string|null $passwordHash
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @return void
     */
    private function populateCustomerWithSecureData($customerModel, $passwordHash = null)
    {
        if ($customerModel->getId()) {
            $customerSecure = $this->customerRegistry->retrieveSecureData($customerModel->getId());

            $customerModel->setRpToken($passwordHash ? null : $customerSecure->getRpToken());
            $customerModel->setRpTokenCreatedAt($passwordHash ? null : $customerSecure->getRpTokenCreatedAt());
            $customerModel->setPasswordHash($passwordHash ?: $customerSecure->getPasswordHash());

            $customerModel->setFailuresNum($customerSecure->getFailuresNum());
            $customerModel->setFirstFailure($customerSecure->getFirstFailure());
            $customerModel->setLockExpires($customerSecure->getLockExpires());
        } elseif ($passwordHash) {
            $customerModel->setPasswordHash($passwordHash);
        }

        if ($passwordHash && $customerModel->getId()) {
            $this->customerRegistry->remove($customerModel->getId());
        }
    }

    /**
     * Set ignore_validation_flag to skip model validation
     *
     * @param array $customerArray
     * @param Customer $customerModel
     * @return void
     */
    private function setValidationFlag($customerArray, $customerModel)
    {
        if (isset($customerArray['ignore_validation_flag'])) {
            $customerModel->setData('ignore_validation_flag', true);
        }
    }

    /**
     * Set customer group id
     *
     * @param Customer $customerModel
     * @param array $customerArr
     * @param array $prevCustomerDataArr
     */
    private function setCustomerGroupId($customerModel, $customerArr, $prevCustomerDataArr)
    {
        if (!isset($customerArr['group_id']) && $prevCustomerDataArr && isset($prevCustomerDataArr['group_id'])) {
            $customerModel->setGroupId($prevCustomerDataArr['group_id']);
        }
    }

    /**
     * Prepare customer data.
     *
     * @param array $customerData
     * @return array
     */
    private function prepareCustomerData(array $customerData): array
    {
        if (isset($customerData[CustomerInterface::CUSTOM_ATTRIBUTES])) {
            foreach ($customerData[CustomerInterface::CUSTOM_ATTRIBUTES] as $attribute) {
                if (empty($attribute['value'])
                    && !empty($attribute['selected_options'])
                    && is_array($attribute['selected_options'])
                ) {
                    $attribute['value'] = implode(',', array_map(function ($option): string {
                        return $option['value'] ?? '';
                    }, $attribute['selected_options']));
                }
                $customerData[$attribute['attribute_code']] = $attribute['value'];
            }
            unset($customerData[CustomerInterface::CUSTOM_ATTRIBUTES]);
        }
        return $customerData;
    }

    /**
     * To validate default address
     *
     * @param CustomerInterface $customer
     * @param string $defaultAddressType
     * @return void
     * @throws InputException
     */
    private function validateDefaultAddress(
        CustomerInterface $customer,
        string $defaultAddressType
    ): void {
        $addressId = $defaultAddressType === CustomerInterface::DEFAULT_BILLING ? $customer->getDefaultBilling()
            : $customer->getDefaultShipping();
        if ($customer->getAddresses()) {
            foreach ($customer->getAddresses() as $address) {
                if ((int) $addressId === (int) $address->getId()) {
                    return;
                }
            }

            throw new InputException(
                __(
                    'The %fieldName value is invalid. Set the correct value and try again.',
                    ['fieldName' => $defaultAddressType]
                )
            );
        }
    }

}
