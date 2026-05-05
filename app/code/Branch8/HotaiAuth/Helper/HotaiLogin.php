<?php

namespace Branch8\HotaiAuth\Helper;

use Branch8\AppSession\Model\TokenManagement;
use Branch8\Customer\Helper\GenerateBuyerEmail;
use Branch8\HotaiAuth\Model\Api\HotaiTokenService;
use Branch8\HotaiCore\Model\Detection\MobileDetect;
use Branch8\HotaiCore\Service\CookieService;
use Carbon\Carbon;
use Exception;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InputMismatchException;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Branch8\HotaiAuth\Service\HotaiAuthService;

class HotaiLogin extends AbstractHelper
{

    const         EVENT_TYPE_LOGIN     = 'login';
    const         EVENT_TYPE_1ST_LOGIN = 'first_login';
    const         EVENT_TYPE_REGISTER  = 'register';
    private const COOKIE_ONE_ID        = 'one_id';

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var CustomerRepositoryInterface
     */
    protected CustomerRepositoryInterface $_customerRepository;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var CustomerRegistry
     */
    protected $customerRegistry;

    protected $generateBuyerEmail;

    protected GroupRepositoryInterface $groupRepository;

    protected HotaiAuthService $hotaiAuthService;

    /**
     * @var CookieService
     */
    protected CookieService $_cookieService;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\PhpCookieManager
     */
    private $cookieMetadataManager;


    private MobileDetect $mobileDetect;

    /**
     * @param Session $customerSession
     * @param CustomerFactory $customerFactory
     * @param StoreManagerInterface $storeManager
     * @param Context $context
     * @param CustomerRepositoryInterface $customerRepository
     * @param CustomerRegistry $customerRegistry
     * @param LoggerInterface $logger
     * @param GenerateBuyerEmail $generateBuyerEmail
     * @param CookieService $cookieService
     * @param GroupRepositoryInterface $groupRepository
     * @param HotaiAuthService $hotaiAuthService
     */
    public function __construct(
        Session $customerSession,
        CustomerFactory $customerFactory,
        StoreManagerInterface $storeManager,
        Context $context,
        CustomerRepositoryInterface $customerRepository,
        CustomerRegistry $customerRegistry,
        LoggerInterface $logger,
        GenerateBuyerEmail $generateBuyerEmail,
        CookieService $cookieService,
        GroupRepositoryInterface $groupRepository,
        HotaiAuthService $hotaiAuthService,
        MobileDetect $mobileDetect,

    ) {
        $this->_customerSession    = $customerSession;
        $this->_customerFactory    = $customerFactory;
        $this->_storeManager       = $storeManager;
        $this->_customerRepository = $customerRepository;
        parent::__construct($context);
        $this->logger               = $logger;
        $this->customerRegistry     = $customerRegistry;
        $this->generateBuyerEmail = $generateBuyerEmail;
        $this->_cookieService       = $cookieService;
        $this->groupRepository      = $groupRepository;
        $this->hotaiAuthService     = $hotaiAuthService;
        $this->mobileDetect = $mobileDetect;
    }

    /**
     * Login
     * @throws Exception
     */
    public function login($customer): void
    {
        $this->_customerSession->setCustomerDataAsLoggedIn($customer);
    }

    /**
     * Login and save with customer phoneNumber
     * @param array $userProfile
     * @return string EVENT_TYPE_LOGIN|EVENT_TYPE_REGISTER
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws Exception
     */
    public function loginOrCreateLogin(array $userProfile)
    {
        /** Find a customer with member_seq */
        $customer  = $this->getCustomerByMemberSeq($userProfile['memberSeq']);

        $isRegister = false;
        if(isset($userProfile['email'])){
            $userProfile['email'] = trim($userProfile['email']);
        }
        if (!$customer->getId()) {
            $this->hotaiAuthService->writeLog('Hotai createAccount start: ' . $userProfile['memberSeq']);
            /** Register */
            $data = [
                'phone_number' => $userProfile['account'],
                'member_seq'   => $userProfile['memberSeq'],
                'firstname'    => $userProfile['name'],
                'lastname'     => '',
                'buyer_email'  => $userProfile['email'],
                'platform'     => $userProfile['registerPlatform'],
            ];
            $this->hotaiAuthService->writeLog('Hotai magento register data: ' . json_encode(
                    $data,
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
                )
            );

            $customer  = $this->creatingAccount($data);
            $isRegister = true;
            $this->hotaiAuthService->writeLog('Hotai createAccount end: ' . $userProfile['memberSeq']);
        }

        $memberSeq                  = $userProfile['memberSeq'];
        $userProfile['buyer_email'] = $userProfile['email'];
        $isUpdateError              = false;
        try {
            $this->hotaiAuthService->writeLog('Hotai magento update data: ' . json_encode(
                    $userProfile,
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
                )
            );
            $this->updateCustomerById($customer->getId(), $userProfile);
        } catch (Exception $e) {
            $this->_logger->info(
                'Error when update customer from Hotai: ' . $userProfile['name'] . ' ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
            $isUpdateError = true;
        }
        //remove from the registry because it is cached
        $this->customerRegistry->remove($customer->getId());

        $customerData = $this->_customerRepository->getById($customer->getId());
        $this->login($customerData);

        if ($isUpdateError) {
            throw new LocalizedException(__('There is an error when update user information from Hotai'));
        }

        $this->setCustomerLoadSections();

        if ($isRegister) {
            $this->generateGa4Data($customer, $memberSeq, HotaiLogin::EVENT_TYPE_REGISTER);
            $this->generateGa4Data($customer, $memberSeq, HotaiLogin::EVENT_TYPE_1ST_LOGIN);
        } elseif (empty($this->_customerSession->getGA4RegisterData())) {
            $this->generateGa4Data($customer, $memberSeq, HotaiLogin::EVENT_TYPE_LOGIN);
        }

       return $isRegister ? self::EVENT_TYPE_REGISTER : self::EVENT_TYPE_LOGIN;
    }

    /**
     * @param $oneId
     * @return void
     * @throws InputException
     * @throws \Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    public function setHotaiOneId($oneId): void
    {
        if (!empty($oneId)) {
            $this->_cookieService->setCookie(self::COOKIE_ONE_ID, $oneId, 60 * 60 * 24 * 30 * 12);
        }
    }

    /**
     * @param $customerId
     * @param $oneId
     * @return void
     * @throws InputException
     * @throws InputMismatchException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function setCustomerOneId($customerId, $oneId): void
    {
        if (empty($oneId)) {
            return;
        }
        $customer = $this->_customerRepository->getById($customerId);
        $customer->setCustomAttribute('one_id', $oneId);
        $this->_customerRepository->save($customer);
    }

    public function generateGa4Data(
        Customer $customer,
        $memberSeq,
        $eventType,
    ) {
        $groupId = $customer->getGroupId();
        $group = $this->groupRepository->getById($groupId);
        $vipLevel = $group->getExtensionAttributes()->getLabel();

        $oneId = $customer->getOneId();
        if (empty($oneId)) {
            $oneId = $this->hotaiAuthService->getHotaiOneId($memberSeq);
            $customer->setOneId($oneId);
        }

        // Render the template with the data
        $data = [
            "user_id" => $oneId,
            "hotaigo_id" => $oneId,
            "vip_level"  => $vipLevel,
            "event"      => $eventType,
        ];

        if ($eventType == self::EVENT_TYPE_REGISTER) {
            $this->_customerSession->setGA4RegisterData($data);
        } else {
            //login or first_login
            $this->_customerSession->setGA4LoginData($data);
        }
        return $this;
    }

    public function getPrefixBuyerEmail()
    {
        return \Branch8\Customer\Helper\Data::BUYER_EMAIL_PREFIX;
    }

    public function generateBuyerEmail()
    {
        return $this->generateBuyerEmail->generateBuyerEmail();
    }

    /**
     * Create new Customer
     *
     * @param array $data
     * @return Customer
     * @throws Exception
     */
    public function creatingAccount($data)
    {
        $customer = $this->_customerFactory->create();
        if (isset($data['buyer_email'])) {
            $data['email'] = $this->generateBuyerEmail();
        }
        $customer->setData($data);
        $customer->save();
        return $customer;
    }


    /**
     * Get customer buy member_seq
     * @param $memberSeq
     * @return mixed
     * @throws LocalizedException
     */
    public function getCustomerByMemberSeq($memberSeq)
    {
        /** @var \Magento\Customer\Model\ResourceModel\Customer\Collection $collection */
        $collection = $this->_customerFactory->create()->getResourceCollection();
        $customer = $collection
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('member_seq', $memberSeq)
            ->getFirstItem();
        return $customer;
    }

    /**
     * @param $customerId
     * @return mixed
     */
    public function getCustomerById($customerId)
    {
        /** @var \Magento\Customer\Model\ResourceModel\Customer\Collection $collection */
        $collection = $this->_customerFactory->create()->getResourceCollection();
        $customer = $collection
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('entity_id', $customerId)
            ->getFirstItem();

        return $customer;
    }

    /**
     * @param $customerId
     * @param $data
     * @return CustomerInterface
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws InputMismatchException
     */
    public function updateCustomerById($customerId, $data): CustomerInterface
    {
        $customer = $this->_customerRepository->getById($customerId);

        $customer->setFirstname($data['name']);
        $customer->setLastname('');
        // $customer->setDob($data['birthday']);
        if (isset($data['email'])) {
            $customer->getExtensionAttributes()->setBuyerEmail($data['email']);
        }
        if (isset($data['buyer_email'])) {
            $customer->getExtensionAttributes()->setBuyerEmail($data['buyer_email']);
        }
        $customer->getExtensionAttributes()->setPhoneNumber($data['account']);//phone number
        $customer->setCustomAttribute('hotai_create_time', Carbon::parse($data['createTime'])->format('Y-m-d H:i:s'));
        $customer->setCustomAttribute('hotai_update_time', Carbon::parse($data['updateTime'])->format('Y-m-d H:i:s'));
        $customer->setCustomAttribute('is_enable', $data['isEnable']);
        $customer->setCustomAttribute('state', $data['state']);
        $customer->setCustomAttribute('tw_id', $data['id']);
        $customer->setGender($this->getGenderUsingSexCol($data['sex']));

        $this->_customerRepository->save($customer);

        return $customer;
    }

    /**
     * @return Bool
     */
    public function isLoggedIn(): bool
    {
        return $this->_customerSession->isLoggedIn();
    }

    /**
     * @return Session
     */
    public function logout(): Session
    {
        return $this->_customerSession->logout();
    }

    /**
     * @param $sex
     * @return int
     */
    public function getGenderUsingSexCol($sex): int
    {
        return match ($sex) {
            'M' => 1,
            'F' => 2,
            default => 0,
        };
    }
    public function setCustomerLoadSections()
    {
        $sections = $this->getCookieManager()->getCookie('customer_load_sections');
        $sections = $sections ? explode(',', $sections) : [];

        $sections[] = 'branch8_customer_data';
        $sections = array_unique($sections);

        $metadata = $this->getCookieMetadataFactory()->createPublicCookieMetadata()
            ->setDuration(3600)
            ->setPath('/')
            ->setHttpOnly(true)
            ->setSecure(\Magento\Framework\App\ObjectManager::getInstance()->get(\Magento\Framework\App\Request\Http::class)->isSecure());
        $this->getCookieManager()->setPublicCookie('customer_load_sections', implode(',', $sections), $metadata);

        // Standard Magento section invalidation for FPC-safe refresh
        $sectionData = ['branch8_customer_data' => time()];
        $sectionMetadata = $this->getCookieMetadataFactory()->createPublicCookieMetadata()
            ->setDuration(3600)
            ->setPath('/')
            ->setHttpOnly(false); // Must be readable by JS customer-data.js
        $this->getCookieManager()->setPublicCookie('section_data_ids', json_encode($sectionData), $sectionMetadata);
    }

    /**
     * Retrieve cookie manager
     *
     * @return \Magento\Framework\Stdlib\Cookie\PhpCookieManager
     * @deprecated 100.1.0
     */
    private function getCookieManager()
    {
        if (!$this->cookieMetadataManager) {
            $this->cookieMetadataManager = \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\Stdlib\Cookie\PhpCookieManager::class
            );
        }
        return $this->cookieMetadataManager;
    }

    /**
     * Retrieve cookie metadata factory
     *
     * @return \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     * @deprecated 100.1.0
     */
    private function getCookieMetadataFactory()
    {
        if (!$this->cookieMetadataFactory) {
            $this->cookieMetadataFactory = \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory::class
            );
        }
        return $this->cookieMetadataFactory;
    }
}
