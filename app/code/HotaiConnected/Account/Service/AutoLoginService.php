<?php
namespace HotaiConnected\Account\Service;

use Magento\Customer\Model\Session;
use Magento\Framework\Session\SessionManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\PhpCookieManager;
use Branch8\HotaiAuth\Exception\HotaiAuthException;
use Branch8\HotaiAuth\Helper\HotaiLogin;
use Branch8\Customer\Helper\Data as CustomerHelper;
use Branch8\HotaiAuth\Helper\HotaiScopeConfig;
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\GuzzleException;
use Carbon\Carbon;
use Webkul\SellerSubAccount\Helper\Data as HelperData;
use Psr\Log\LoggerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\CustomerRegistry;

class AutoLoginService
{
    const HOTAI_LOGIN_WITH_TOKEN_APPSRC_KEY = 'appsrc';
    const HOTAI_LOGIN_WITH_TOKEN_TOKEN_KEY = 'token';
    const CONTENT_TYPE = 'application/json; charset=UTF-8';

    protected $cookieMetadataFactory;
    protected $cookieMetadataManager;
    protected $hotaiLoginHelper;
    protected $request;
    protected $messageManager;
    protected $subAccountHelper;
    protected $customerHelper;
    protected $client;
    protected $clientBack;
    protected $hotaiScopeConfig;
    protected $sessionManager;

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $_customerRepository;

    /**
     * @var CustomerRegistry
     */
    protected $customerRegistry;

    /**
     * @var array 允許的額外參數列表
     */
    protected $allowedAdditionalParams = [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'receivedPointsTime',
        'receivedPoints'
    ];

    private $logger;
    private $appId;
    private $aesKey;
    private $aesIv;
    private $hotaiExternExchangeAesKey;
    private $hotaiExternExchangeAesIv;
    private $hotaiExternExchangeAppKey;
    private $apiBackDomain;
    private $apiDomain;
    private $hotaiExternExchangeIsProduction;
    private string|null $appVersion;
    private string|null $apiVersion;

    public function __construct(
        HotaiLogin $hotaiLoginHelper,
        Session $_customerSession,
        RequestInterface $request,
        CustomerHelper $customerHelper,
        ManagerInterface $messageManager,
        HelperData $subAccountHelper,
        CookieMetadataFactory $cookieMetadataFactory,
        PhpCookieManager $cookieMetadataManager,
        ClientFactory $clientFactory,
        HotaiScopeConfig $hotaiScopeConfig,
        LoggerInterface $logger,
        CustomerRepositoryInterface $_customerRepository,
        CustomerRegistry $customerRegistry,
        CustomerFactory $customerFactory,
        SessionManager $sessionManager
    ) {
        $this->hotaiLoginHelper = $hotaiLoginHelper;
        $this->request = $request;
        $this->customerHelper = $customerHelper;
        $this->messageManager = $messageManager;
        $this->subAccountHelper = $subAccountHelper;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->cookieMetadataManager = $cookieMetadataManager;
        $this->hotaiScopeConfig = $hotaiScopeConfig;
        $this->logger = $logger;
        $this->_customerSession = $_customerSession;
        $this->_customerFactory = $customerFactory;
        $this->customerRegistry = $customerRegistry;
        $this->_customerRepository = $_customerRepository;
        $this->sessionManager = $sessionManager;

        $this->initScopeValue();

        // 初始化 HTTP clients
        $this->client      = $clientFactory->create(
            [
                'config' => [
                    'base_uri' => $this->apiDomain
                ]
            ]
        );
        $this->clientBack  = $clientFactory->create(
            [
                'config' => [
                    'base_uri' => $this->apiBackDomain
                ]
            ]
        );
    }

    /**
     * 初始化配置值
     */
    private function initScopeValue(): void
    {
        $this->apiDomain = $this->hotaiScopeConfig->getAuthScopeConfig(
            HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_API_DOMAIN
        );
        $this->appId = $this->hotaiScopeConfig->getAuthScopeConfig(
            HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_APP_ID
        );
        $this->aesKey = $this->hotaiScopeConfig->getAuthScopeConfig(
            HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_AES_KEY
        );
        $this->aesIv = $this->hotaiScopeConfig->getAuthScopeConfig(
            HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_AES_IV
        );
        $this->apiBackDomain = $this->hotaiScopeConfig->getAuthScopeConfig(
            HotaiScopeConfig::HOTAI_EXTERNAL_EXCHANGE_CONFIG_API_DOMAIN
        );
        $this->hotaiExternExchangeIsProduction = $this->hotaiScopeConfig->getAuthScopeConfig(
            HotaiScopeConfig::HOTAI_EXTERNAL_EXCHANGE_CONFIG_IS_PRODUCTION
        );
        $this->hotaiExternExchangeAesKey = $this->hotaiScopeConfig->getAuthScopeConfig(
            HotaiScopeConfig::HOTAI_EXTERNAL_EXCHANGE_CONFIG_PATH_AK
        );
        $this->hotaiExternExchangeAesIv = $this->hotaiScopeConfig->getAuthScopeConfig(
            HotaiScopeConfig::HOTAI_EXTERNAL_EXCHANGE_CONFIG_PATH_AES_IV
        );
        $this->hotaiExternExchangeAppKey = $this->hotaiScopeConfig->getAuthScopeConfig(
            HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_APP_KEY
        );
        $this->appVersion = $this->hotaiScopeConfig->getAuthScopeConfig(
            HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_APP_VERSION
        );
        $this->apiVersion = $this->hotaiScopeConfig->getAuthScopeConfig(
            HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_API_VERSION
        );
    }

    /**
     * 刷新session
     *
     * @return bool
     * @throws LocalizedException
     */
    public function refreshLogin(): bool
    {
        try {
            $sessionData = $this->sessionManager->getData();
            if (!is_array($sessionData) || !isset($sessionData['hotai_token'])) {
                $this->logger->info(
                    '[refresh_session] Unable to get token session',
                    [
                        'customerId' => $this->_customerSession->getCustomerId()
                    ]
                );
                $this->logout();
                return false;
            }

            $hotaiToken = $sessionData['hotai_token'];

            // 取過期時間
            $tokenExpiry = $this->getJwtTokenExpiry($hotaiToken['accessToken']);

            // 無法獲取過期時間
            if ($tokenExpiry === false) {
                $this->logger->info(
                    '[refresh_session] Unable to get JWT token expiry',
                    [
                        'accessToken' => $hotaiToken['accessToken'],
                        'refreshToken' => $hotaiToken['refreshToken'],
                        'tokenExpiry' => date('Y-m-d H:i:s', $tokenExpiry),
                        'customerId' => $this->_customerSession->getCustomerId()
                    ]
                );
                $this->logout();
                return false;
            }

            // 剩餘5分鐘或過期 則刷新
            $currentTime = time();
            if ($currentTime >= ($tokenExpiry - 300)) {
                if (!$this->refreshToken($hotaiToken)) {
                    $this->logger->info(
                        '[refresh_session] API refresh token error',
                        [
                            'accessToken' => $hotaiToken['accessToken'],
                            'refreshToken' => $hotaiToken['refreshToken'],
                            'tokenExpiry' => date('Y-m-d H:i:s', $tokenExpiry),
                            'customerId' => $this->_customerSession->getCustomerId()
                        ]
                    );
                    $this->logout();
                    return false;
                }
            }

            // 重新生成 session ID 並延長時間
            $this->_customerSession->regenerateId();
            $lifetime = 1500; // 25分鐘
            $this->_customerSession->setSessionExpiry(time() + $lifetime);

            $this->logger->info(
                '[refresh_session] Success',
                [
                    'accessToken' => $hotaiToken['accessToken'],
                    'refreshToken' => $hotaiToken['refreshToken'],
                    'tokenExpiry' => date('Y-m-d H:i:s', $tokenExpiry),
                    'customerId' => $this->_customerSession->getCustomerId()
                ]
            );

            return true;
        } catch (\Exception $e) {
            $this->logger->error('[refresh_session] Exception Stack trace: ' . $e->getMessage());
            $this->logger->error('[refresh_session] Exception Stack trace: ' . $e->getTraceAsString());
            $this->logout();
            return false;
        }
    }

    /**
     * 確認token狀態
     */
    public function verifyToken($hotaiToken)
    {
        try {
            $content = [
                'accessToken'  => $hotaiToken['accessToken'],
            ];

            $response = $this->client->post("Connect/VerificationToken", [
                'headers' => [
                    'APP_ID'       => $this->appId,
                    'Content-Type' => self::CONTENT_TYPE
                ],
                'json'    => $content
            ]);

            $content = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            if (isset($content['isVerification']) && $content['isVerification'] == false) {
                $this->logger->info('[refresh_session] Hotai verifyToken response:' . print_r($content, true));
                return false;
            }

            $this->logger->info('[refresh_session] Hotai verifyToken response:' . print_r($content, true));
            return true;

        } catch (GuzzleException $exception) {
            $this->logger->error('[refresh_session] Hotai verifyToken exception:' . print_r($exception->getMessage(), true));
            return false;
        }
    }

    /**
     * 取JWT token的過期時間
     *
     * @param string
     * @return int|false
     */
    public function getJwtTokenExpiry($token)
    {
        try {
            $tokenParts = explode('.', $token);

            if (count($tokenParts) != 3) {
                return false;
            }

            $payload = $tokenParts[1];
            $payload = str_replace(['-', '_'], ['+', '/'], $payload);
            $payload = base64_decode($payload);

            if ($payload === false) {
                return false;
            }

            $decodedPayload = json_decode($payload, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return false;
            }

            if (!isset($decodedPayload['exp'])) {
                return false;
            }

            return (int) $decodedPayload['exp'];

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 刷新token
     */
    public function refreshToken($hotaiToken)
    {
        $content = [
            'accessToken'  => $hotaiToken['accessToken'],
            'refreshToken' => $hotaiToken['refreshToken'],
        ];

        try {
            $response = $this->client->post("Connect/RefreshToken", [
                'headers' => [
                    'APP_ID'       => $this->appId,
                    'Content-Type' => self::CONTENT_TYPE
                ],
                'json'    => $content
            ]);

            $content = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            if (isset($content['access_token'])) {
                $this->sessionManager->unsetData('hotai_token');
                $this->sessionManager->setHotaiToken([
                    'tokenType'    => $content['token_type'],
                    'accessToken'  => $content['access_token'],
                    'refreshToken' => $content['refresh_token'],
                    'expiredAt'    => Carbon::now()->addMinutes(25),
                ]);
                $this->logger->info(
                    '[refresh_session] Hotai refreshToken success',
                    [
                        'tokenType'    => $content['token_type'],
                        'accessToken'  => $content['access_token'],
                        'refreshToken' => $content['refresh_token'],
                        'expiredAt'    => Carbon::now()->addMinutes(25)
                    ]
                );
                return true;
            }

            $this->logger->error('[refresh_session] Hotai refreshToken error:' . print_r($content, true));
            return false;

        } catch (GuzzleException $exception) {
            $this->logger->error('[refresh_session] Hotai refreshToken exception:' . print_r($exception->getMessage(), true));
            return false;
        }
    }

    /**
     * 登出用戶
     * 清除客戶端 session
     *
     * @return bool
     */
    public function logout(): bool
    {
        try {
            $this->sessionManager->unsetData('hotai_token');

            $this->_customerSession->logout();

            return true;
        } catch (\Exception $e) {
            $this->logger->error('[refresh_session] Exception: ' . $e->getMessage());
            $this->logger->error('[refresh_session] Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }
}
