<?php

namespace HotaiConnected\FinancialReconciliation\Plugin\Api;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Webapi\Rest\Response;
use Magento\Framework\Webapi\Exception as WebapiException;
use Magento\Framework\Phrase;
use Psr\Log\LoggerInterface;
use Magento\Backend\Model\Auth\Session as AuthSession;
use Magento\User\Model\UserFactory;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Customer\Api\CustomerRepositoryInterface;

use HotaiConnected\FinancialReconciliation\Helper\SessionEncryptor;

class AuthInterceptor
{
    protected RequestInterface $request;
    protected Response $response;
    protected SessionEncryptor $sessionEncryptor;
    protected UserFactory $userFactory;
    protected SessionManagerInterface $sessionManager;
    protected CustomerSession $customerSession;
    protected CustomerRepositoryInterface $customerRepository;

    /**
     * @var AuthSession
     */
    private AuthSession $authSession;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(
        RequestInterface $request,
        Response $response,
        SessionManagerInterface $sessionManager,
        AuthSession $authSession,
        UserFactory $userFactory,
        LoggerInterface $logger,
        SessionEncryptor $sessionEncryptor,
        CustomerSession $customerSession,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->logger = $logger;
        $this->sessionManager = $sessionManager;
        $this->request = $request;
        $this->userFactory = $userFactory;
        $this->response = $response;
        $this->authSession = $authSession;
        $this->sessionEncryptor = $sessionEncryptor;
        $this->customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
    }

    private $aclMapping = [
        'POST:/rest/V1/reconciliation' => 'HotaiConnected_FinancialReconciliation::checkout_area_insert',
        'GET:/rest/V1/reconciliation' => 'HotaiConnected_FinancialReconciliation::checkout_area_view',
        'GET:/rest/V1/reconciliation/status' => 'HotaiConnected_FinancialReconciliation::finance_automation',
        'GET:/rest/V1/reconciliation/sellers' => 'HotaiConnected_FinancialReconciliation::finance_automation',
        'GET:/rest/V1/reconciliation/ecpay-seller-revenue-detail' => 'HotaiConnected_FinancialReconciliation::checkout_area_detail_export',
        'POST:/rest/V1/reconciliation/ecpay-seller-revenue-jobs'  => 'HotaiConnected_FinancialReconciliation::checkout_area_detail_export',
        'POST:/rest/V1/reconciliation/delete' => 'HotaiConnected_FinancialReconciliation::checkout_area_delete',
        'GET:/rest/V1/reconciliation/ecpay-seller-revenue-summarize' => 'HotaiConnected_FinancialReconciliation::checkout_area_summarize',
        'GET:/rest/V1/reconciliation/ecpay-seller-revenue-summarize/all' => 'HotaiConnected_FinancialReconciliation::checkout_area_summarize',
        'POST:/rest/V1/reconciliation/sellers' => 'HotaiConnected_FinancialReconciliation::checkout_area_detail_send',
        'POST:/rest/V1/reconciliation/invoices' => 'HotaiConnected_FinancialReconciliation::checkout_area_invoice_insert',
        'PUT:/rest/V1/reconciliation/invoices' => 'HotaiConnected_FinancialReconciliation::checkout_area_invoice_update',
        'DELETE:/rest/V1/reconciliation/invoices' => 'HotaiConnected_FinancialReconciliation::checkout_area_invoice_delete',
        'POST:/rest/V1/reconciliation/exception-auth' => 'HotaiConnected_FinancialReconciliation::checkout_area_exception_insert',
        'GET:/rest/V1/reconciliation/exception-auth' => 'HotaiConnected_FinancialReconciliation::checkout_area_exception_select',
        'GET:/rest/V1/exception-auth' => 'HotaiConnected_FinancialReconciliation::review_function_list',
        'POST:/rest/V1/exception-auth' => 'HotaiConnected_FinancialReconciliation::review_function_approvement',
        'POST:/rest/V1/exception-auth/export' => 'HotaiConnected_FinancialReconciliation::review_function_export',
        'GET:/rest/V1/reconciliation/ticket/export' => 'HotaiConnected_FinancialReconciliation::ticket_reconciliation_export',
        'GET:/rest/V1/reconciliation/ticket/list' => 'HotaiConnected_FinancialReconciliation::ticket_reconciliation_list',
        'GET:/rest/V1/ecpay-order-logs/list' => 'HotaiConnected_FinancialReconciliation::order_checkout_export_list',
        'GET:/rest/V1/ecpay-order-logs/export' => 'HotaiConnected_FinancialReconciliation::order_checkout_export_download'
    ];

    public function aroundDispatch(
        \Magento\Webapi\Controller\Rest $subject,
        \Closure $proceed,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $requestUri = $request->getRequestUri();
        $method = $request->getMethod();
        $decryptedSessionId = null;
        if (strpos($requestUri, 'V1/reconciliation') !== false || 
            strpos($requestUri, 'exception-auth') !== false ||
            strpos($requestUri, 'ecpay-order-logs') !== false) {
            $authHeader = $request->getHeader('X-MAGENTO-AUTH');
            if (!empty($authHeader)) {
                $decryptedSessionId = $this->sessionEncryptor->decrypt($authHeader);
                if ($decryptedSessionId === false) {
                    $phrase = new Phrase('Invalid or corrupted "X-MAGENTO-AUTH" header. Unauthorized access.');
                    throw new WebapiException($phrase, 0, WebapiException::HTTP_UNAUTHORIZED);
                }
            } else {
                $phrase = new Phrase('The "X-MAGENTO-AUTH" header is missing. Unauthorized access.');
                throw new WebapiException($phrase, 0, WebapiException::HTTP_UNAUTHORIZED);
            }
            if (!empty($decryptedSessionId)) {
                $userId = $this->sessionEncryptor->getUserIdFromSessionId($decryptedSessionId);
                if ($userId && !empty($userId)) {
                    $adminUser = $this->userFactory->create()->load($userId);
                    if ($adminUser->getId()) {
                        if (session_status() !== PHP_SESSION_ACTIVE) {
                            try {
                                $this->sessionManager->start();
                            } catch (\Throwable $e) {
                                $this->logger->error('Session start failed: ' . $e->getMessage());
                            }
                        }
                        $this->authSession->setUser($adminUser);
                        $this->authSession->refreshAcl();

                        // Manual ACL Check
                        $path = $request->getPathInfo(); 
                        // Normalize path: remove /rest/default/ or /rest/V1/ etc if needed, 
                        // but here we just match suffix or use strict matching if path is clean.
                        // Assuming path starts with /V1/... based on webapi.xml
                        
                        // We iterate to find matching path
                        $lookupKey = $method . ':' . $path;
                        $matchedResource = $this->aclMapping[$lookupKey] ?? null;

                        if ($matchedResource) {
                            if (!$this->authSession->isAllowed($matchedResource)) {
                                $this->logger->warning(__CLASS__ . " Access Denied for resource: $matchedResource");
                                throw new WebapiException(new Phrase("Access denied for resource: $matchedResource"), 0, WebapiException::HTTP_FORBIDDEN);
                            }
                        } else {
                            $this->logger->info(__CLASS__ . " No ACL mapping found for $method $path, allowing by default.");
                            throw new WebapiException(new Phrase('The endpoint does not exist or you do not have permission to access it.'), 0, WebapiException::HTTP_FORBIDDEN);

                        }
                    }
                }
            }
        }
        else if (strpos($requestUri, '/V1/marketplace/reconciliation') !== false) {
            $authHeader = $request->getHeader('X-MAGENTO-AUTH');
            if (!empty($authHeader)) {
                $decryptedSessionId = $this->sessionEncryptor->decrypt($authHeader);
                if ($decryptedSessionId !== false) {
                    $customerId = $this->sessionEncryptor->decodeMPSessionById($decryptedSessionId);
                    if ($customerId) {
                        try {
                            if (session_status() !== PHP_SESSION_ACTIVE) {
                                $this->sessionManager->start();
                            }
                            $customer = $this->customerRepository->getById($customerId);
                            $this->customerSession->setCustomerDataAsLoggedIn($customer);
                        } catch (\Throwable $e) {
                            $this->logger->error('Marketplace session start failed: ' . $e->getMessage());
                        }
                    }
                }
            }
        }

        return $proceed($request);
    }
}
