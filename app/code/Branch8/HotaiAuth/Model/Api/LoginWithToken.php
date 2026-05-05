<?php

namespace Branch8\HotaiAuth\Model\Api;

use Branch8\AppSession\Model\TokenManagement;
use Branch8\HotaiAuth\Api\LoginWithTokenInterface;
use Branch8\HotaiAuth\Service\HotaiAuthAppLoginService;
use Branch8\HotaiCore\Model\Detection\MobileDetect;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Webapi\Rest\Response;
use Branch8\HotaiAuth\Service\HotaiAuthLoginService;
use Branch8\Customer\Service\PendingEmployeeMatchingService;
use Magento\Framework\Webapi\Rest\Request;
use Psr\Log\LoggerInterface;

class LoginWithToken implements LoginWithTokenInterface
{
    /** @var Request */
    protected $request;

    /** @var Response */
    protected $response;

    /**
     * @var HotaiAuthLoginService
     */
    protected HotaiAuthLoginService $hotaiAuthLoginService;

    /**
     * @var HotaiAuthAppLoginService
     */
    protected HotaiAuthAppLoginService $hotaiAuthAppLoginService;

    private Session $customerSession;
    private MobileDetect $mobileDetect;
    private TokenManagement $tokenManagement;
    private CustomerRepositoryInterface $customerRepository;
    private PendingEmployeeMatchingService $pendingEmployeeMatchingService;
    private LoggerInterface $logger;

    /**
     * @param Request $request
     * @param Response $response
     * @param HotaiAuthLoginService $hotaiAuthLoginService
     * @param HotaiAuthAppLoginService $hotaiAuthAppLoginService
     * @param Session $customerSession
     * @param TokenManagement $tokenManagement
     * @param MobileDetect $mobileDetect
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param CustomerRepositoryInterface $customerRepository
     * @param PendingEmployeeMatchingService $pendingEmployeeMatchingService
     * @param LoggerInterface $logger
     */
    public function __construct(
        Request $request,
        Response $response,
        HotaiAuthLoginService $hotaiAuthLoginService,
        HotaiAuthAppLoginService $hotaiAuthAppLoginService,
        Session $customerSession,
        TokenManagement $tokenManagement,
        MobileDetect $mobileDetect,
        protected readonly CookieManagerInterface $cookieManager,
        protected readonly CookieMetadataFactory  $cookieMetadataFactory,
        protected readonly ScopeConfigInterface   $scopeConfig,
        CustomerRepositoryInterface $customerRepository,
        PendingEmployeeMatchingService $pendingEmployeeMatchingService,
        LoggerInterface $logger,
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->hotaiAuthLoginService = $hotaiAuthLoginService;
        $this->hotaiAuthAppLoginService = $hotaiAuthAppLoginService;
        $this->customerSession = $customerSession;
        $this->tokenManagement = $tokenManagement;
        $this->mobileDetect = $mobileDetect;
        $this->customerRepository = $customerRepository;
        $this->pendingEmployeeMatchingService = $pendingEmployeeMatchingService;
        $this->logger = $logger;
    }

    public function login()
    {
        if ($this->mobileDetect->isHotaiApp()) {
            $loginResponse = $this->hotaiAuthAppLoginService->handleLoginWithApi();
            $this->hotaiAuthAppLoginService->writeAppLog('Hotai APP $loginResponse: ' . print_r($loginResponse, true));

            // Process pending employee matching before token generation
            $this->processPendingEmployee();

            $customer = $this->customerSession->getCustomer();
            if ($customer->getId()) {
                $this->hotaiAuthAppLoginService->writeAppLog('Hotai APP customer->getId(): ' . print_r($customer->getId(), true));
                $this->tokenManagement->revokeCustomerAccessToken($customer->getId());
                $loginResponse['magentoToken'] = $this->tokenManagement->generateCustomerToken($customer);

                $this->hotaiAuthAppLoginService->writeAppLog('Hotai APP $loginResponse finish: ' . print_r($loginResponse, true));
            }
        } else {
            $loginResponse = $this->hotaiAuthLoginService->handleLoginWithApi();

            // Process pending employee matching for web login
            $this->processPendingEmployee();
        }

        return $this->sendResponse($loginResponse);
    }

    /**
     * Process pending employee matching for logged-in customer
     *
     * @return void
     */
    private function processPendingEmployee(): void
    {
        try {
            $customerId = $this->customerSession->getCustomerId();
            if ($customerId) {
                // 用 CustomerRepositoryInterface::getById() 載入完整 customer（含 EAV 屬性如 phone_number）
                // customerSession->getCustomerData() 不一定有載入自訂 EAV 屬性
                $customerData = $this->customerRepository->getById($customerId);
                $this->pendingEmployeeMatchingService->processCustomerRegistration($customerData);
            }
        } catch (\Exception $e) {
            $this->logger->error('LoginWithToken: Error processing pending employee matching', [
                'customer_id' => $this->customerSession->getCustomerId(),
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function sendResponse(array $responseData): void
    {
        $this->response
            ->setHeader('Content-Type', 'application/json', true)
            ->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains', true)
            ->setBody(json_encode($responseData))
            ->sendResponse();
    }
}
