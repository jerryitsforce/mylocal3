<?php
namespace Branch8\HotaiAuth\Service;

use Branch8\Customer\Helper\Data;
use Branch8\HotaiAuth\Exception\HotaiAuthException;
use Branch8\HotaiAuth\Helper\HotaiLogin;
use Branch8\HotaiAuth\Logger\Logger;
use DateTime;
use JsonException;
use Branch8\HotaiCore\Model\Detection\MobileDetect;
use Branch8\HotaiCore\Helper\RedisLock;
use Magento\Customer\Model\Account\Redirect as AccountRedirect;
use Magento\Customer\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Session\SessionManager;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PhpCookieManager;
use Webkul\SellerSubAccount\Helper\Data as HelperData;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\ResourceConnection;
use Zend_Log_Exception;

class HotaiAuthLoginService
{
    public const HOTAI_LOGIN_WITH_TOKEN_APPSRC_KEY = 'appsrc';

    public const HOTAI_LOGIN_WITH_TOKEN_TOKEN_KEY = 'token';

    /**
     * @var array
     */
    public const ALLOWED_ADDITIONAL_PARAMS = [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'receivedPointsTime',
        'receivedPoints'
    ];

    protected array $allowedAdditionalParams = self::ALLOWED_ADDITIONAL_PARAMS;

    /**
     * @var FormKey
     */
    protected $formKey;

    /**
     * @var CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * @var PhpCookieManager
     */
    private $cookieMetadataManager;

    /**
     * @var AccountRedirect
     */
    protected $accountRedirect;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var Data
     */
    protected $b8CustomerHelper;

    /**
     * @var HotaiLogin
     */
    protected $hotaiLoginHelper;

    /**
     * @var HotaiAuthService
     */
    protected $hotaiAuthService;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;
    /**
     * @var HelperData
     */
    protected $subAccountHelper;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var ResourceConnection
     */
    protected $resource;

    private MobileDetect $mobileDetect;
    private SessionManager $sessionManager;
    private \Magento\Framework\Event\ManagerInterface $eventManager;
    private Logger $hotaiAppAuthLogger;
    private RedisLock $redisLock;

    /**
     * Cache lock key prefix for login API
     */
    public const LOGIN_API_LOCK_KEY_PREFIX = 'hotai_auth_login_api_lock_';


    /**
     * @param HotaiLogin $hotaiLoginHelper
     * @param Session $customerSession
     * @param HotaiAuthService $hotaiAuthService
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @param Data $b8CustomerHelper
     * @param AccountRedirect $accountRedirect
     * @param FormKey $formKey
     * @param RedirectFactory $resultRedirectFactory
     * @param ManagerInterface $messageManager
     * @param HelperData $subAccountHelper
     * @param UrlInterface $urlBuilder
     * @param ResourceConnection $resource
     * @param MobileDetect $mobileDetect
     * @param SessionManager $sessionManager
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param Logger $hotaiAppAuthLogger
     * @param RedisLock $redisLock
     * @throws LocalizedException
     */
    public function __construct(
        HotaiLogin $hotaiLoginHelper,
        Session $customerSession,
        HotaiAuthService $hotaiAuthService,
        RequestInterface $request,
        ResponseInterface $response,
        Data $b8CustomerHelper,
        AccountRedirect $accountRedirect,
        FormKey $formKey,
        RedirectFactory $resultRedirectFactory,
        ManagerInterface $messageManager,
        HelperData $subAccountHelper,
        UrlInterface $urlBuilder,
        ResourceConnection $resource,
        MobileDetect $mobileDetect,
        SessionManager $sessionManager,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        Logger $hotaiAppAuthLogger,
        RedisLock $redisLock
    ) {
        $this->hotaiLoginHelper = $hotaiLoginHelper;
        $this->customerSession = $customerSession;
        $this->hotaiAuthService = $hotaiAuthService;
        $this->request = $request;
        $this->response = $response;
        $this->b8CustomerHelper = $b8CustomerHelper;
        $this->accountRedirect = $accountRedirect;
        $this->formKey = $formKey;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->messageManager = $messageManager;
        $this->request->setParam('form_key', $this->formKey->getFormKey());
        $this->subAccountHelper = $subAccountHelper;
        $this->resource = $resource;
        $this->urlBuilder = $urlBuilder;
        $this->mobileDetect = $mobileDetect;
        $this->sessionManager = $sessionManager;
        $this->eventManager = $eventManager;
        $this->hotaiAppAuthLogger = $hotaiAppAuthLogger;
        $this->redisLock = $redisLock;
    }

    /**
     * Handle a login process through API
     *
     * @throws LocalizedException
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     * @throws JsonException
     */
    public function handleLoginWithApi()
    {
        $redirectUrl = '/';
        $isPost = $this->request->isPost();
        $paramsKeys = $isPost ? $this->request->getPost() : $this->request->getParams();
        $this->hotaiAuthService->writeLog('Hotai LoginWithToken request params:' . print_r($paramsKeys, true));

        // Generate a lock key based on request parameters to prevent duplicate requests
        $lockKey = $this->getLoginApiLockKey($paramsKeys);
        $this->hotaiAuthService->writeLog('Hotai LoginWithToken: generate lock key: ' . $lockKey);

        // Check if lock exists (the same request is already processing)
        if ($this->redisLock->isLocked($lockKey)) {
            $this->hotaiAuthService->writeLog('Hotai LoginWithToken: Duplicate request detected, lock key: ' . $lockKey);
            $this->messageManager->addErrorMessage(__('Request is already being processed. Please wait.'));
            return ['redirect_url' => $redirectUrl, 'success' => false, 'message' => 'Request is already being processed'];
        }

        // Acquire lock
        $this->redisLock->acquire($lockKey, 30);
        $this->hotaiAuthService->writeLog('Hotai LoginWithToken: Lock acquired, key: ' . $lockKey);

        try {
            $content = json_decode($this->request->getContent(), true);

            // 先嘗試用 getParam 獲取參數
            $appSrc = $this->request->getParam(self::HOTAI_LOGIN_WITH_TOKEN_APPSRC_KEY);
            $accessToken = $this->request->getParam(self::HOTAI_LOGIN_WITH_TOKEN_TOKEN_KEY);
            $redirectUrl = urldecode($this->request->getParam('redirect_url') ?? $redirectUrl);

            // 如果 getParam 抓不到參數，則從 content 中獲取
            if ($appSrc === null || $accessToken === null) {
                $appSrc = $content[self::HOTAI_LOGIN_WITH_TOKEN_APPSRC_KEY] ?? $appSrc;
                $accessToken = $content[self::HOTAI_LOGIN_WITH_TOKEN_TOKEN_KEY] ?? $accessToken;
                $redirectUrl = urldecode($content['redirect_url'] ?? $redirectUrl);
            }

            $this->hotaiAuthService->writeLog('Hotai LoginWithToken redirect Url of request:' . print_r($redirectUrl, true));

            foreach ($this->allowedAdditionalParams as $param) {

                $value = $this->request->getParam($param);

                if ($value === null) {
                    $value = $content[$param] ?? null;
                }

                if ($value) {
                    $separator = !str_contains($redirectUrl, '?') ? '?' : '&';
                    $redirectUrl .= $separator . $param . '=' . $value;
                }
            }
            $this->hotaiAuthService->writeLog('Hotai LoginWithToken Redirect Url of redirection:' . print_r($redirectUrl, true));
//
//            if ($this->hotaiLoginHelper->isLoggedIn()
//                && !$this->b8CustomerHelper->isSeller()
//                && !$this->b8CustomerHelper->isWaitForSeller()
//                && !$this->subAccountHelper->isSubAccount()
//                && !$this->mobileDetect->isHotaiApp()
//            ) {
//                $this->hotaiAuthService->writeLog('Hotai LoginWithToken setUrl-$redirectUrl' . print_r($redirectUrl, true));
//                $customerId = $this->customerSession->getCustomerId();
//                if ($customerId !== null) {
//                    $this->insertLoginLog($customerId, $paramsKeys);
//                }
//            }

            /**
             * Token 處理流程：
             * 1. normalizeToken() 在 getDecryptToken 中自動處理 URL encoding
             * 2. normalizeToken() 在 getReacquireToken 中處理解密後 token 的格式問題
             * 3. 保留 fallback 重試機制作為最後防線
             */
            try {
                /** Decrypt external source Token */
                $decryptToken = $this->getDecryptToken($accessToken, $appSrc);

                /** Get exchange Token */
                $reacquireTokenResult = $this->getReacquireToken($decryptToken, $appSrc);
                $this->hotaiAppAuthLogger->info('[LoginWithToken]' . json_encode(['token' => $decryptToken, 'reacquire_token' => $reacquireTokenResult['accessToken'] ?? '']));

            } catch (HotaiAuthException $firstTryEx) {
                // Fallback: 如果 normalizeToken 沒有正確處理，嘗試手動 urldecode
                $this->hotaiAuthService->writeLog('Hotai LoginWithToken firstTryEx (fallback retry): ' . $firstTryEx->getMessage());
                $accessToken = urldecode($accessToken);
                /** Decrypt external source Token */
                $decryptToken = $this->getDecryptToken($accessToken, $appSrc);

                /** Get exchange Token */
                try {
                    $reacquireTokenResult = $this->getReacquireToken($decryptToken, $appSrc);
                } catch (HotaiAuthException $retryEx) {
                    $this->hotaiAuthService->writeLog('Hotai LoginWithToken externalDecryptToken error after urldecode accessToken :' . print_r($retryEx->getMessage(), true));

                    if (str_contains($retryEx->getMessage(), '請輸入Token')) { //$accessToken = urldecode($accessToken); -> externalDecryptToken -> token null
                        throw $firstTryEx;
                    }
                    throw $retryEx;
                }
                $this->hotaiAppAuthLogger->info('[LoginWithToken]' . json_encode(['token' => $decryptToken, 'reacquire_token' => $reacquireTokenResult['accessToken'] ?? '']));
            }

            $userProfile = $this->hotaiAuthService->getUserProfile();
            $this->hotaiAuthService->writeLog('Hotai loginWithToken loginOrCreateLogin userProfile content: ' . json_encode($userProfile));
            $this->hotaiLoginHelper->loginOrCreateLogin($userProfile);

            $this->hotaiAppAuthLogger->info('[LoginWithToken] Login successful - MemberSeq: ' . $userProfile['memberSeq'] . ' Account: ' . $userProfile['account']);


            if ($this->getCookieManager()->getCookie('mage-cache-sessid')) {
                $metadata = $this->getCookieMetadataFactory()->createCookieMetadata();
                $metadata->setPath('/');
                $this->getCookieManager()->deleteCookie('mage-cache-sessid', $metadata);
            }

            $this->eventManager->dispatch(
                'hotai_auth_save_latest_token',
                [
                    'hotai_token' => $this->sessionManager->getData('hotai_token'),
                    'member_seq' => $userProfile['memberSeq'] ?? null
                ]
            );

        } catch (HotaiAuthException $h) {
            $this->hotaiAuthService->writeLog('Hotai LoginWithToken HotaiAuthException error:' . print_r($h->getMessage(), true));

            if ($this->mobileDetect->isHotaiApp()) {
                //請重新登入
                $this->messageManager->addErrorMessage(__('Please login again'));
            } else {
                $this->messageManager->addErrorMessage($h->getMessage());
            }

        } catch (\Exception $e) {
            $this->hotaiAuthService->writeLog('Hotai LoginWithToken Exception error:' . print_r($e->getMessage(), true));
            $this->messageManager->addErrorMessage($e->getMessage());
        } finally {
            // Always release lock in finally block to ensure it's released even if an exception occurs
            $this->redisLock->release($lockKey);
            $this->hotaiAuthService->writeLog('Hotai LoginWithToken: Lock released, key: ' . $lockKey);
        }

        $customerId = $this->customerSession->getCustomerId();
        if ($customerId !== null) {
            $this->insertLoginLog($customerId, $paramsKeys);
        }

        if ($redirectUrl !== '/') {
            // $resultRedirect = $this->resultRedirectFactory->create();
            $this->hotaiAuthService->writeLog('Hotai LoginWithToken setUrl-$redirectUrl' . print_r($redirectUrl, true));
            // $resultRedirect->setUrl($redirectUrl);
            // return $resultRedirect;
            return ['redirect_url' => $redirectUrl, 'success' => true];
        }

        // return $this->accountRedirect->getRedirect();
        return ['redirect_url' => $this->customerSession->getBeforeAuthUrl(true)];
    }


    /**
     * Retrieve cookie manager
     *
     * @deprecated 100.1.0
     * @return     \Magento\Framework\Stdlib\Cookie\PhpCookieManager
     */
    private function getCookieManager()
    {
        if (!$this->cookieMetadataManager) {
            $this->cookieMetadataManager = \Magento\Framework\App\ObjectManager::getInstance()->get(
                PhpCookieManager::class
            );
        }
        return $this->cookieMetadataManager;
    }

    /**
     * Retrieve cookie metadata factory
     *
     * @deprecated 100.1.0
     * @return     CookieMetadataFactory
     */
    private function getCookieMetadataFactory()
    {
        if (!$this->cookieMetadataFactory) {
            $this->cookieMetadataFactory = \Magento\Framework\App\ObjectManager::getInstance()->get(
                CookieMetadataFactory::class
            );
        }
        return $this->cookieMetadataFactory;
    }

    /**
     * @param $customerId
     * @param array $params
     * @return void
     * @throws JsonException
     */
    private function insertLoginLog($customerId, array $params): void
    {
        $connection = $this->resource->getConnection(ResourceConnection::DEFAULT_CONNECTION);
        $data = [
            'customer_id' => $customerId,
            'last_login_at' => (new DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT),
            'device' => $this->mobileDetect->getDeviceType(),
        ];

        if (!empty($params)) {
            foreach ($this->allowedAdditionalParams as $param) {
                if (isset($params[$param])) {
                    $temp[$param] = $params[$param];
                }
            }
            if (!empty($temp)) {
                $data['utm'] = json_encode($temp, JSON_THROW_ON_ERROR);
            }
        }

        $connection->insert(
            $this->resource->getTableName('branch_custom_customer_log'),
            $data
        );
    }

    /**
     * @param $accessToken
     * @param $appSrc
     * @return mixed|string|string[]
     * @throws HotaiAuthException
     */
    private function getDecryptToken($accessToken, $appSrc): mixed
    {
        // Normalize accessToken before decryption to handle URL encoding issues
        $accessToken = $this->normalizeToken($accessToken);
        $this->hotaiAuthService->writeLog('Hotai getDecryptToken normalized accessToken');

        // HTGO-1206 特殊處理 TLINE 的 coupon 登入 因為昕力要改開了天價
        if ($appSrc === 'TLINE' && $this->request->getParam('utm_source') === 'toyota' &&  $this->request->getParam('utm_medium') === 'coupon') {
            $decryptToken = $this->hotaiAuthService->externalDecryptToken($accessToken, 'TAPP');
        }else {
            $decryptToken = $this->hotaiAuthService->externalDecryptToken($accessToken, $appSrc);
        }

        return $decryptToken;
    }

    /**
     * Normalize token to ensure consistent format
     * Handles cases where token may or may not be URL encoded
     *
     * @param string $token
     * @return string
     */
    private function normalizeToken(?string $token): ?string
    {
        // If token is empty, return as-is
        if (empty($token)) {
            return $token;
        }

        // Check if token appears to be URL encoded (contains %XX patterns)
        // Common URL encoded characters in base64: %2B (+), %2F (/), %3D (=)
        if (preg_match('/%[0-9A-Fa-f]{2}/', $token)) {
            $decodedToken = urldecode($token);
            $this->hotaiAuthService->writeLog('Hotai normalizeToken: URL decoded token');
            return $decodedToken;
        }

        // Check if token has spaces that should be plus signs (URL form encoding issue)
        // In URL form encoding, '+' becomes ' ' (space)
        if (strpos($token, ' ') !== false && strpos($token, '+') === false) {
            $fixedToken = str_replace(' ', '+', $token);
            $this->hotaiAuthService->writeLog('Hotai normalizeToken: Fixed space to plus sign');
            return $fixedToken;
        }

        return $token;
    }

    /**
     * @throws FileSystemException
     * @throws JsonException
     * @throws HotaiAuthException
     * @throws Zend_Log_Exception
     */
    private function getReacquireToken($decryptToken, $appSrc)
    {
        // Normalize token before sending to API
        $decryptToken = $this->normalizeToken($decryptToken);
        $this->hotaiAuthService->writeLog('Hotai getReacquireToken normalized token: ' . $decryptToken);

        // 先執行 1.12
        $reacquireTokenResult = $this->hotaiAuthService->reacquireToken($decryptToken, $appSrc);

        if ($this->mobileDetect->isHotaiApp()) {
            // 如果是APP 執行 1.7 再執行 1.12
            $ssoToken = $this->hotaiAuthService->reacquireGetSSOToken($reacquireTokenResult['access_token'], $this->hotaiAuthService->hotaiAppAppId);
            $reacquireTokenResult = $this->hotaiAuthService->reacquireToken($ssoToken['token'], $this->hotaiAuthService->hotaiWebAppId);
        }

        return $reacquireTokenResult;
    }

    /**
     * Generate a lock key based on request parameters
     * Uses token and appsrc to create a unique identifier for the request
     *
     * @param array $params
     * @return string
     */
    private function getLoginApiLockKey(array $params): string
    {
        // Get token and appsrc from params, fallback to request params if not found
        $token = $params[self::HOTAI_LOGIN_WITH_TOKEN_TOKEN_KEY]
            ?? $this->request->getParam(self::HOTAI_LOGIN_WITH_TOKEN_TOKEN_KEY, '');
        $appSrc = $params[self::HOTAI_LOGIN_WITH_TOKEN_APPSRC_KEY]
            ?? $this->request->getParam(self::HOTAI_LOGIN_WITH_TOKEN_APPSRC_KEY, '');

        // Generate a lock key using token and appsrc
        return $this->redisLock->generateLockKey(
            self::LOGIN_API_LOCK_KEY_PREFIX,
            $token . '_' . $appSrc
        );
    }
}
