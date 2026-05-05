<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_SellerSubAccount
 * @author    Webkul Software Private Limited
 * @copyright Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
namespace Branch8\Customer\Plugin;

use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Customer\Mapper as CustomerMapper;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Framework\Api\DataObjectHelper;
use Webkul\SellerSubAccount\Helper\Data as WkSellerSubAccountHelperData;

/**
 * Webkul SellerSubAccount Helper Data.
 */
class SellerSubAccountHelperData
{

    /**
     * @var CustomerRepositoryInterface
     */
    public $_customerRepository;

    /**
     * @var CustomerMapper
     */
    public $_customerMapper;

    /**
     * @var CustomerInterfaceFactory
     */
    public $_customerFactory;

    /**
     * @var AccountManagementInterface
     */
    public $_accountManagement;

    /**
     * @var DataObjectHelper
     */
    public $_dataObjectHelper;

    /**
     * @var CustomerFactory
     */
    public $customerModFactory;

    /**
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerMapper $customerMapper
     * @param CustomerInterfaceFactory $customerFactory
     * @param AccountManagementInterface $accountManagement
     * @param DataObjectHelper $dataObjectHelper
     * @param CustomerFactory $customerModFactory
     */
    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        CustomerMapper $customerMapper,
        CustomerInterfaceFactory $customerFactory,
        AccountManagementInterface $accountManagement,
        DataObjectHelper $dataObjectHelper,
        CustomerFactory $customerModFactory
    ) {
        $this->_customerRepository = $customerRepository;
        $this->_customerMapper = $customerMapper;
        $this->_customerFactory = $customerFactory;
        $this->customerModFactory=$customerModFactory;
        $this->_accountManagement = $accountManagement;
        $this->_dataObjectHelper = $dataObjectHelper;
    }

    /**
     * Around save customer data.
     *
     * @param WkSellerSubAccountHelperData $subject
     * @param \Closure $proceed
     * @param array $customerData
     * @param int $customerId
     * @param int $websiteId
     *
     * @return array
     */
    public function aroundSaveCustomerData(
        WkSellerSubAccountHelperData $subject,
        \Closure $proceed,
        $customerData,
        $customerId = 0,
        $websiteId = 0
    ){
        if (!empty($customerData)) {
            $customerData['customerId'] = $subject->getCustomerIdByEmail($customerData);
            if (!empty($customerData['customerId'])) {
                $customerId = $customerData['customerId'];
                if($websiteId == 0){
                    /**
                     * Fix issue delete sub account and re-add on BE
                     */
                    $customerInterface = $subject->getCustomerById($customerId);
                    $websiteId = $customerInterface->getWebsiteId();
                }
            }
            try {
                // optional fields might be set in request for future processing
                // by observers in other modules
                $customerData['website_id'] = $websiteId;
                $customerData['group_id'] = $subject->getAccountGroup();
                $customerData['disable_auto_group_change'] = 1;

                if (array_key_exists('prefix', $customerData)) {
                    if (isset($customerData['prefix'])) {
                         $customerData['prefix'] = $customerData['prefix'];
                    }
                }
                if (array_key_exists('middlename', $customerData)) {
                    if (isset($customerData['middlename'])) {
                         $customerData['middlename'] = $customerData['middlename'];
                    }
                }
                if (array_key_exists('suffix', $customerData)) {
                    if (isset($customerData['suffix'])) {
                         $customerData['suffix'] = $customerData['suffix'];
                    }
                }
                if (array_key_exists('company', $customerData)) {
                    if (isset($customerData['company'])) {
                         $customerData['company'] = $customerData['company'];
                    }
                }

                if (array_key_exists('dob', $customerData)) {
                    if (isset($customerData['dob'])) {
                         $customerData['dob'] = $customerData['dob'];
                    }
                }

                if (array_key_exists('gender', $customerData)) {
                    if (isset($customerData['gender'])) {
                         $customerData['gender'] = $customerData['gender'];
                    }
                }

                if (isset($customerData['taxvat'])) {
                    $customerData['taxvat'] = $customerData['taxvat'];
                }

                if (array_key_exists('address', $customerData)) {
                    if (array_key_exists('country_id', $customerData)) {

                        if (isset($customerData['country_id'])) {
                            $customerData['address']['country_id']=$customerData['country_id'];
                        }
                    }

                    if (array_key_exists('company', $customerData)) {

                        if (isset($customerData['company'])) {
                            $customerData['address']['company']=$customerData['company'];
                        }
                    }

                    if (array_key_exists('city', $customerData)) {

                        if (isset($customerData['city'])) {
                            $customerData['address']['city']=$customerData['city'];
                        }
                    }

                    if (array_key_exists('postalcode', $customerData)) {

                        if (isset($customerData['postalcode'])) {
                            $customerData['address']['postalcode']=$customerData['postalcode'];
                        }
                    }

                    if (array_key_exists('region_id', $customerData)) {

                        if (isset($customerData['region_id'])) {
                            $customerData['address']['region_id']=$customerData['region_id'];
                        }
                    }

                    if (array_key_exists('telephone', $customerData)) {

                        if (isset($customerData['telephone'])) {
                            $customerData['address']['telephone']=$customerData['telephone'];
                        }
                    }

                    if (array_key_exists('fax', $customerData)) {

                        if (isset($customerData['fax'])) {
                            $customerData['address']['fax']=$customerData['fax'];
                        }
                    }
                    if (array_key_exists('vat_id', $customerData)) {

                        if (isset($customerData['vat_id'])) {
                            $customerData['address']['vat_id']=$customerData['vat_id'];
                        }
                    }

                    if ($subject->isReqAddressEnable()) {
                        $customerData['create_address']=1;
                    } else {
                        $customerData['create_address']=0;
                    }

                    $customerData['default_billing']=1;
                    $customerData['default_shipping']=1;
                }

                $customerData['confirmation'] = '';
                $customerData['sendemail_store_id'] = 1;

                if ($subject->dobMandatory() && array_key_exists('dob', $customerData)) {
                    $birthday = $customerData['dob'];
                    $timestamp = strtotime($birthday);
                    $customerDob = date("Y-m-d", $timestamp);
                    $customerData['dob'] = $customerDob;
                } else {

                    $customerDob = date("Y-m-d");
                    $customerData['dob'] = $customerDob;
                }
                if ($customerId) {
                    $currentCustomer = $this->_customerRepository->getById($customerId);
                    $customerData = array_merge(
                        $this->_customerMapper->toFlatArray($currentCustomer),
                        $customerData
                    );
                    $customerData['id'] = $customerId;
                }
                /** @var CustomerInterface $customer */
                $customer = $this->_customerFactory->create();
                if (array_key_exists('address', $customerData)) {
                    $addresses = $subject->createAddresses($customerData);
                    $addresses = $addresses === null ? [] : [$addresses];
                    $customer->setAddresses($addresses);
                }
                $this->_dataObjectHelper->populateWithArray(
                    $customer,
                    $customerData,
                    \Magento\Customer\Api\Data\CustomerInterface::class
                );
                // Save customer
                if ($customerId) {
                    $this->_customerRepository->save($customer);

                    $subject->getEmailNotification()->credentialsChanged(
                        $customer,
                        $currentCustomer->getEmail()
                    );
                } else {
                    $customer->setCustomAttribute('platform', 'seller');
                    $customer = $this->_accountManagement->createAccount($customer);
                    $customerId = $customer->getId();
                }
                $this->customerModFactory->create()
                    ->load($customer->getId())
                    ->setData('platform', 'seller')
                    ->setGroupId($customerData['group_id'])
                    ->setData('member_seq', null)
                    ->setData('buyer_email', null)
                    ->setData('phone_number', null)
                    ->save();
            } catch (\Exception $e) {
                return ['error'=>1, 'message'=>$e->getMessage()];
            }
        }
        return ['error'=>0, 'customer_id'=>$customerId];
    }
}
