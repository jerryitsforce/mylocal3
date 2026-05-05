<?php

namespace Branch8\OneStepCheckout\Controller\Ajax;

use Branch8\OneStepCheckout\Model\Source\HotaiAddressType;
use Magento\Customer\Api\Data\AddressExtensionFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\RegionInterface;

class AddressBookManagement extends \Magento\Framework\App\Action\Action
{

    /**
     * @var Context
     */
    private $context;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var AddressInterfaceFactory
     */
    protected $dataAddressFactory;

    /**
     * @var AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * @var RegionInterface
     */
    protected $regionInterface;

    private AddressExtensionFactory $addressExtensionFactory;

    /**
     * @var \Magento\Directory\Helper\Data
     */
    protected $directoryHelper;


    /**
     * @var \Magento\Directory\Model\ResourceModel\Region\CollectionFactory
     */
    protected $regCollectionFactory;


    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param JsonFactory $resultJsonFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param AddressInterfaceFactory $dataAddressFactory
     * @param AddressRepositoryInterface $addressRepository
     * @param RegionInterface $regionInterface
     * @param \Magento\Directory\Helper\Data $directoryHelper
     * @param \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regCollectionFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        JsonFactory $resultJsonFactory,
        \Magento\Customer\Model\Session $customerSession,
        AddressInterfaceFactory $dataAddressFactory,
        AddressRepositoryInterface $addressRepository,
        RegionInterface $regionInterface,
        AddressExtensionFactory $addressExtensionFactory,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regCollectionFactory
    )
    {
        $this->context = $context;
        $this->resultPageFactory = $resultPageFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerSession = $customerSession;
        $this->dataAddressFactory = $dataAddressFactory;
        $this->addressRepository = $addressRepository;
        $this->regionInterface = $regionInterface;
        $this->addressExtensionFactory = $addressExtensionFactory;
        $this->directoryHelper = $directoryHelper;
        $this->regCollectionFactory = $regCollectionFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $data = $this->context->getRequest()->getParams();

        $customerId = $this->customerSession->getCustomerId();
        $action = $data['action'];
        $resultData = [];
        $resultData['success'] = true;
        $resultData['data'] = $data;
        $resultData['message'] = '';

        $addressType = HotaiAddressType::ADDRESS_TYPE_NORMAL;
        if (isset($data['hotai_address_type'])) {
            $addressType = $data['hotai_address_type'];
        }

        // if (isset($data['region']) && $data['region'] == '' && isset($data['region_id']) && $addressType == HotaiAddressType::ADDRESS_TYPE_NORMAL) {
        //     $regionData = $this->getRegionInfo($data['region_id']);
        //     if(isset($regionData)) {
        //         $resultData['data']['region'] = $regionData['name'];
        //     }
        // }

        if ($action == 'add') {
            //add new address
            $address = $this->dataAddressFactory->create();
            if (isset($data['region'])) {
                $this->regionInterface->setRegion($data['region']);
            }
            if (isset($data['region_code'])) {
                $this->regionInterface->setRegionCode($data['region_code']);
            }
            if (isset($data['region_id'])) {
                $this->regionInterface->setRegionId($data['region_id']);
            }

            /** \Magento\Customer\Model\Data\Address $address */
            $address->setFirstname($data['firstname']);
            $address->setLastname($data['lastname']);
            $address->setTelephone($data['telephone']);
            $address->setStreet($data['street']);
            $address->setCity($data['city']);
            $address->setCountryId($data['country_id']);
            if (isset($data['region_id'])) {
                $address->setRegionId($data['region_id']);
            }
            $address->setRegion($this->regionInterface);
            $address->setCustomerId($customerId);

            $address->setCustomAttribute('hotai_address_type', $addressType);

            if (isset($data['custom_attributes']) && !empty($data['custom_attributes'])) {
                if (isset($data['custom_attributes']['cvs_store_code'])) {
                    $address->setCustomAttribute('cvs_store_code', $data['custom_attributes']['cvs_store_code']);
                }
                if (isset($data['custom_attributes']['cvs_store_name'])) {
                    $address->setCustomAttribute('cvs_store_name', $data['custom_attributes']['cvs_store_name']);
                }
                if (isset($data['custom_attributes']['cvs_store_servicetype'])) {
                    $address->setCustomAttribute('cvs_store_servicetype', $data['custom_attributes']['cvs_store_servicetype']);
                }
                if (isset($data['custom_attributes']['cvs_store_outside']) && $data['custom_attributes']['cvs_store_outside']) {
                    $address->setCustomAttribute('cvs_store_outside', true);
                } else {
                    $address->setCustomAttribute('cvs_store_outside', false);
                }
            }

            if($addressType === HotaiAddressType::ADDRESS_TYPE_CONVENIENCE_STORE) {
                $extensionAttributes = $address->getExtensionAttributes();
                $extensionAttributes = $extensionAttributes ?: $this->addressExtensionFactory->create();
                if (isset($data['default_shipping'])) {
                    $extensionAttributes->setIsDefaultConvenienceStore(true);
                    $resultData['default_convenience_store'] = true;
                }
            } else if (isset($data['default_shipping'])) {
                $address->setIsDefaultShipping(true);
                $address->setIsDefaultBilling(true);
            }

            try {
                if (isset($data['shipping_save_in_address_book']) && $data['shipping_save_in_address_book']) {
                    $newAddress = $this->addressRepository->save($address);

                    // $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/new-address.log');
                    // $logger = new \Zend_Log();
                    // $logger->addWriter($writer);
                    // $logger->info(print_r(json_encode($newAddress->getExtensionAttributes()), true));
                    // $logger->info(print_r(json_encode($newAddress->getRegion()), true));

                    $resultData['data']['id'] = $newAddress->getId();
                    // $resultData['data']['default_shipping'] = true;
                } else {
                    $resultData['data']['id'] = 0;
                }
                $resultData['data']['customer_id'] = $customerId;
            } catch (\Exception $exception) {
                $resultData['message'] = $exception->getMessage();
                $resultData['success'] = false;
                $this->messageManager->addError($exception->getMessage());
            }
        } else if ($action == 'edit' && isset($data['address_id'])) {
            //update address
            $addressId = $data['address_id'];
            $saveInAddressBook = filter_var($data['shipping_save_in_address_book'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($addressId || $saveInAddressBook) {
                try {
                    $address = $addressId ? $this->addressRepository->getById($addressId) : $this->dataAddressFactory->create();

                    if (isset($data['region'])) {
                        $this->regionInterface->setRegion($data['region']);
                    }
                    if (isset($data['region_code'])) {
                        $this->regionInterface->setRegionCode($data['region_code']);
                    }
                    if (isset($data['region_id']) && $data['region_id']) {
                        $this->regionInterface->setRegionId($data['region_id']);
                    }

                    if (isset($data['custom_attributes']) && !empty($data['custom_attributes'])) {
                        if (isset($data['custom_attributes']['cvs_store_code'])) {
                            $address->setCustomAttribute('cvs_store_code', $data['custom_attributes']['cvs_store_code']);
                        }
                        if (isset($data['custom_attributes']['cvs_store_name'])) {
                            $address->setCustomAttribute('cvs_store_name', $data['custom_attributes']['cvs_store_name']);
                        }
                        if (isset($data['custom_attributes']['cvs_store_servicetype'])) {
                            $address->setCustomAttribute('cvs_store_servicetype', $data['custom_attributes']['cvs_store_servicetype']);
                        }
                        if (isset($data['custom_attributes']['cvs_store_outside']) && $data['custom_attributes']['cvs_store_outside']) {
                            $address->setCustomAttribute('cvs_store_outside', true);
                        }  else {
                            $address->setCustomAttribute('cvs_store_outside', false);
                        }
                    }

                    $address->setFirstname($data['firstname']);
                    $address->setLastname($data['lastname']);
                    $address->setTelephone($data['telephone']);
                    $address->setStreet($data['street']);
                    $address->setCity($data['city']);
                    $address->setCountryId($data['country_id']);
                    if (isset($data['region_id'])) {
                        $address->setRegionId($data['region_id']);
                    }
                    $address->setRegion($this->regionInterface);

                    if ($saveInAddressBook && !$addressId) { //save add new -> save as default
                        $address->setCustomerId($customerId);
                        $address->setCustomAttribute('hotai_address_type', $addressType);


                        if($addressType === HotaiAddressType::ADDRESS_TYPE_CONVENIENCE_STORE) {
                            $extensionAttributes = $address->getExtensionAttributes();
                            $extensionAttributes = $extensionAttributes ?: $this->addressExtensionFactory->create();
                            if (isset($data['default_shipping'])) {
                                $extensionAttributes->setIsDefaultConvenienceStore(true);
                                $resultData['default_convenience_store'] = true;
                            }
                        } else if (isset($data['default_shipping'])) {
                            $address->setIsDefaultShipping(true);
                            $address->setIsDefaultBilling(true);
                        }
                        // $resultData['data']['default_shipping'] = true;
                    }

                    $this->addressRepository->save($address);

                    $resultData['data']['customer_id'] = $customerId;
                    $resultData['data']['customer_address_id'] = $address->getId();

                }  catch(\Exception $e) {
                    $resultData['message'] = $e->getMessage();
                    $resultData['success'] = false;
                    $this->messageManager->addError($e->getMessage());
                }
            }
        } else if ($action == 'delete' && isset($data['address_id'])) {
            //delete address
            $addressId = $data['address_id'];
            if ($addressId) {
                try {
                    $this->addressRepository->deleteById($addressId);
                }  catch(\Exception $e) {
                    $resultData['message'] = $e->getMessage();
                    $resultData['success'] = false;
                    $this->messageManager->addError($e->getMessage());
                }
            }
        }

        $result = $this->resultJsonFactory->create();
        $result->setData($resultData);
        return $result;
    }

    protected function getRegionInfo($regionId)
    {
        if(!isset($regionId)) {
            return;
        }

        $regions = $this->directoryHelper->getRegionData();
        

        if(isset($regions['TW'])) {
           return $regions['TW'][$regionId];
        }
        return;
    }
}
