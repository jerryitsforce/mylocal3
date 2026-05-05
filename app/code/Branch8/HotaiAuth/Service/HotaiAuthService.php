<?php

namespace Branch8\HotaiAuth\Service;

use AllowDynamicProperties;
use Branch8\HotaiAuth\Api\HotaiAuthServiceInterface;
use Branch8\HotaiAuth\Exception\HotaiAuthException;
use Branch8\HotaiAuth\Helper\GeneralHelper;
use Branch8\HotaiAuth\Helper\HotaiScopeConfig;
use Branch8\HotaiAuth\Logger\Logger;
use Branch8\HotaiCore\Model\Detection\MobileDetect;
use Branch8\HotaiCore\Service\CookieService;
use Carbon\Carbon;
use Exception;
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\ResponseFactory;
use JsonException;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Session\Config\ConfigInterface;
use Magento\Framework\Session\SessionManager;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Magento\Framework\Url\Decoder;
use Zend_Log_Exception;
use Magento\Framework\UrlInterface;

#[AllowDynamicProperties] class HotaiAuthService
{
    private const CONTENT_TYPE = 'application/json; charset=UTF-8';
    private const COOKIE_CODE_VERIFIER = 'hotai_code_verifier';
    private const COOKIE_REDIRECT_URL = 'hotai_redirect_url';

    public string|null $hotaiWebAppId;
    public string|null $hotaiWebAesKey;
    public string|null $hotaiWebAesIv;

    public string|null $appVersionForWeb;
    public string|null $apiVersionForWeb;
    public string|null $hotaiAppAppId;

    private string|null $apiDomain;
    private string|null $apiBackDomain;
    private string|null $apiRedirectUri;
    private string|null $appId;
    private string|null $aesKey;
    private string|null $aesIv;
    private string|null $appVersion;
    private string|null $apiVersion;
    private string|null $clientId;
    private string|null $clientSecret;

    private string|null $hotaiGoTravelApiDomain;
    private string|null $hotaiGoTravelAppId;
    private string|null $hotaiGoTravelAesKey;
    private string|null $hotaiGoTravelAesIv;


    private $hotaiExternExchangeIsProduction;

    private string|null $hotaiExternExchangeAesKey;
    private string|null $hotaiExternExchangeAesIv;

    private array $hotaiExternExchangeAesKeyProduction = [];
    private array $hotaiExternExchangeAesIvProduction = [];

    private string|null $hotaiExternExchangeAppKey;

    private string|null $apiOneIdApiDomain;
    private string|null $hotaiOneIdService;
    private MobileDetect $mobileDetect;
    private Logger $hotaiAppAuthLogger;

    public function __construct(
        ClientFactory     $clientFactory,
        ResponseFactory   $responseFactory,
        SessionManager    $sessionManager,
        HotaiScopeConfig  $hotaiScopeConfig,
        RedirectInterface $redirect,
        ConfigInterface   $sessionConfig,
        GeneralHelper     $loggerInterface,
        CookieService     $cookieService,
        UrlInterface      $url,
        Decoder           $decoder,
        MobileDetect      $mobileDetect,
        Logger            $hotaiAppAuthLogger
    )
    {
        $this->responseFactory = $responseFactory;
        $this->sessionManager = $sessionManager;
        $this->_loggerInterface = $loggerInterface;
        $this->hotaiScopeConfig = $hotaiScopeConfig;
        $this->_redirect = $redirect;
        $this->_cookieService = $cookieService;
        $this->sessionConfig = $sessionConfig;
        $this->url = $url;
        $this->mobileDetect = $mobileDetect;
        $this->hotaiAppAuthLogger = $hotaiAppAuthLogger;
        $this->initScopeValue();
        $this->client = $clientFactory->create([
            'config' => [
                'base_uri' => $this->apiDomain,
                'timeout' => 5,           // 總請求超時 5 秒
                'connect_timeout' => 2    // 連線超時 2 秒
            ]
        ]);
        $this->clientBack = $clientFactory->create([
            'config' => [
                'base_uri' => $this->apiBackDomain,
                'timeout' => 5,
                'connect_timeout' => 2
            ]
        ]);
        $this->clientOneId = $clientFactory->create([
            'config' => [
                'base_uri' => $this->apiOneIdApiDomain,
                'timeout' => 5,
                'connect_timeout' => 2
            ]
        ]);

        $this->decoder = $decoder;
    }

    /**
     * @return void
     */
    private function initScopeValue(): void
    {
        if ($this->mobileDetect->isHotaiApp()) {
            $this->apiDomain      = $this->hotaiScopeConfig->getAuthScopeConfig(
                HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_HOTAI_APP_API_DOMAIN
            );
            $this->apiRedirectUri = $this->hotaiScopeConfig->getAuthScopeConfig(
                HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_HOTAI_APP_API_REDIRECT_URI
            );
            $this->appId          = $this->hotaiScopeConfig->getAuthScopeConfig(
                HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_HOTAI_APP_APP_ID
            );
            $this->aesKey         = $this->hotaiScopeConfig->getAuthScopeConfig(
                HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_HOTAI_APP_AES_KEY
            );
            $this->aesIv          = $this->hotaiScopeConfig->getAuthScopeConfig(
                HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_HOTAI_APP_AES_IV
            );
            $this->clientId       = $this->hotaiScopeConfig->getAuthScopeConfig(
                HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_HOTAI_APP_CLIENT_ID
            );
            $this->clientSecret   = $this->hotaiScopeConfig->getAuthScopeConfig(
                HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_HOTAI_APP_CLIENT_SECRET
            );
            $this->appVersion       = $this->hotaiScopeConfig->getAuthScopeConfig(
                HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_HOTAI_APP_APP_VERSION
            );
            $this->apiVersion   = $this->hotaiScopeConfig->getAuthScopeConfig(
                HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_HOTAI_APP_API_VERSION
            );
            $this->hotaiExternExchangeAppKey = $this->hotaiScopeConfig->getAuthScopeConfig(
                HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_HOTAI_APP_APP_KEY
            );
        } else {
            $this->apiDomain      = $this->hotaiScopeConfig->getAuthScopeConfig(
                HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_API_DOMAIN
            );
            $this->apiRedirectUri = $this->hotaiScopeConfig->getAuthScopeConfig(
                HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_API_REDIRECT_URI
            );
            $this->appId          = $this->hotaiScopeConfig->getAuthScopeConfig(
                HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_APP_ID
            );
            $this->aesKey         = $this->hotaiScopeConfig->getAuthScopeConfig(
                HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_AES_KEY
            );
            $this->aesIv          = $this->hotaiScopeConfig->getAuthScopeConfig(
                HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_AES_IV
            );
            $this->clientId       = $this->hotaiScopeConfig->getAuthScopeConfig(
                HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_CLIENT_ID
            );
            $this->clientSecret   = $this->hotaiScopeConfig->getAuthScopeConfig(
                HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_CLIENT_SECRET
            );
            $this->appVersion       = $this->hotaiScopeConfig->getAuthScopeConfig(
                HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_APP_VERSION
            );
            $this->apiVersion   = $this->hotaiScopeConfig->getAuthScopeConfig(
                HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_API_VERSION
            );
            $this->hotaiExternExchangeAppKey = $this->hotaiScopeConfig->getAuthScopeConfig(
                HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_APP_KEY
            );
        }

        /** First, get a default site app key */
//        $this->hotaiExternExchangeAppKey = $this->hotaiScopeConfig->getAuthScopeConfig(
//            HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_APP_KEY
//        );

        $this->hotaiWebAppId          = $this->hotaiScopeConfig->getAuthScopeConfig(
            HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_APP_ID
        );

        $this->hotaiWebAesKey         = $this->hotaiScopeConfig->getAuthScopeConfig(
            HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_AES_KEY
        );
        $this->hotaiWebAesIv          = $this->hotaiScopeConfig->getAuthScopeConfig(
            HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_AES_IV
        );

        $this->appVersionForWeb       = $this->hotaiScopeConfig->getAuthScopeConfig(
            HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_APP_VERSION
        );
        $this->apiVersionForWeb   = $this->hotaiScopeConfig->getAuthScopeConfig(
            HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_API_VERSION
        );

        $this->hotaiAppAppId      = $this->hotaiScopeConfig->getAuthScopeConfig(
            HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_HOTAI_APP_APP_ID
        );

        /** Hotai Go Travel */
        $this->hotaiGoTravelApiDomain = $this->hotaiScopeConfig->getAuthScopeConfig(
            HotaiScopeConfig::HOTAI_GO_TRAVEL_CONFIG_PATH_API_DOMAIN
        );
        $this->hotaiGoTravelAppId = $this->hotaiScopeConfig->getAuthScopeConfig(
            HotaiScopeConfig::HOTAI_GO_TRAVEL_CONFIG_PATH_APP_ID
        );
        $this->hotaiGoTravelAesKey = $this->hotaiScopeConfig->getAuthScopeConfig(
            HotaiScopeConfig::HOTAI_GO_TRAVEL_CONFIG_PATH_AES_KEY
        );
        $this->hotaiGoTravelAesIv = $this->hotaiScopeConfig->getAuthScopeConfig(
            HotaiScopeConfig::HOTAI_GO_TRAVEL_CONFIG_PATH_AES_IV
        );

        /** Hotai External Exchange */
        $this->hotaiExternExchangeIsProduction = $this->hotaiScopeConfig->getAuthScopeConfig(
            HotaiScopeConfig::HOTAI_EXTERNAL_EXCHANGE_CONFIG_IS_PRODUCTION
        );
        $this->apiBackDomain = $this->hotaiScopeConfig->getAuthScopeConfig(
            HotaiScopeConfig::HOTAI_EXTERNAL_EXCHANGE_CONFIG_API_DOMAIN
        );
        $this->hotaiExternExchangeAesKey = $this->hotaiScopeConfig->getAuthScopeConfig(
            HotaiScopeConfig::HOTAI_EXTERNAL_EXCHANGE_CONFIG_PATH_AK
        );
        $this->hotaiExternExchangeAesIv = $this->hotaiScopeConfig->getAuthScopeConfig(
            HotaiScopeConfig::HOTAI_EXTERNAL_EXCHANGE_CONFIG_PATH_AES_IV
        );


        $this->hotaiExternExchangeAesKeyProduction = $this->hotaiScopeConfig->getHotaiExternExchangeProductionAESKey();
        $this->hotaiExternExchangeAesIvProduction = $this->hotaiScopeConfig->getHotaiExternExchangeProductionAESIV();


        /** Hotai One ID */
        $this->apiOneIdApiDomain = $this->hotaiScopeConfig->getAuthScopeConfig(
            HotaiScopeConfig::HOTAI_ONE_ID_API_DOMAIN
        );
        $this->hotaiOneIdService = $this->hotaiScopeConfig->getAuthScopeConfig(
            HotaiScopeConfig::HOTAI_ONE_ID_SERVICE
        );
    }

    /**
     * @return string
     * @throws JsonException
     * @throws Exception
     */
    public function getLoginUrl(): string
    {
        $codeChallenge = $this->codeChallenge();

        $content = [
            'clientId' => $this->clientId,
            'codeChallenge' => $codeChallenge,
            'codeChallengeMethod' => 'S256',
            'redirectUri' => $this->apiRedirectUri
        ];

        try {
            $response = $this->client->post("Connect/RedirectUri", [
                'headers' => [
                    'APP_ID' => $this->appId,
                    'Content-Type' => self::CONTENT_TYPE
                ],
                'json' => $content
            ]);

            $content = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
            $url = isset($content['data']) ? $content['data']['authRedirectUri'] : '';
        } catch (GuzzleException $exception) {
            $url = '';
        }

        return $url;
    }

    /**
     * @param $device
     * @return array
     * @throws JsonException
     */
    public function getGroupApps($device): array
    {
        $content = [
            'clientId' => $this->clientId,
            "Device" => $device
        ];

        try {
            $response = $this->client->post("SystemInfo/GroupApps", [
                'headers' => [
                    'APP_ID' => $this->appId,
                    'Content-Type' => self::CONTENT_TYPE
                ],
                'json' => $content
            ]);

            $content = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
            $data = $content['data'] ?? [];
        } catch (GuzzleException $exception) {
            $data = ['error' => $exception->getMessage()];
        }

        return $data;
    }

    /**
     * @return string
     * @throws JsonException
     * @throws Exception
     */
    public function getModifierUrl(): string
    {
        $content = [
            'clientId' => $this->clientId,
        ];

        try {
            $accessToken = $this->sessionManager->getHotaiToken();

            $this->writeLog('Hotai getModifierUrl info:' . print_r($accessToken, true));

            $response = $this->client->post("Connect/ModifyRedirectUri", [
                'headers' => [
                    'Authorization' => "Bearer $accessToken",
                    'APP_ID' => $this->appId,
                    'Content-Type' => self::CONTENT_TYPE
                ],
                'json'    => $content
            ]);

            $jsonStr = $response->getBody()->getContents();
            $jsonStr = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $jsonStr);

            $content = json_decode($jsonStr, true, 512, JSON_THROW_ON_ERROR);

            $url = isset($content['data']) ? $content['data']['modifyRedirectUri'] : '';
        } catch (GuzzleException $exception) {
            $this->writeLog('Hotai getModifierUrl exception:' . print_r($exception->getMessage(), true));
            $url = '';
        }

        return $url;
    }

    /**
     * @return mixed|string|string[]
     * @throws HotaiAuthException|JsonException
     */
    public function getRedirectUrl(): mixed
    {
        $redirectUrl = $this->sessionManager->getCustomerRefererUrl();

        if (empty($redirectUrl)) {
            $redirectUrl = $this->_cookieService->getCookie(self::COOKIE_REDIRECT_URL);
        }

        return $redirectUrl ?? "";
    }

    /**
     * @param string $code
     * @param string $memberSeq
     * @return array|mixed
     * @throws FailureToSendException
     * @throws HotaiAuthException
     * @throws InputException
     * @throws JsonException
     */
    public function getToken(string $code, string $memberSeq)
    {
        $codeVerifier = $this->sessionManager->getCodeVerifier();

        if (empty($codeVerifier)) {
            $codeVerifier = $this->_cookieService->getCookie(self::COOKIE_CODE_VERIFIER);
        }

        $content = [
            'grantType' => 'authorization_code',
            'clientId' => $this->clientId,
            'codeVerifier' => $codeVerifier,
            'redirectUri' => $this->apiRedirectUri,
            'code' => $code,
            'clientSecret' => $this->clientSecret,
            'memberSeq' => $memberSeq
        ];

        try {
            $response = $this->client->post("Connect/Token", [
                'headers' => [
                    'APP_ID' => $this->appId,
                    'Content-Type' => self::CONTENT_TYPE
                ],
                'json' => $content
            ]);

            $content = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            if (isset($content['data'])) {
                $this->sessionManager->setHotaiToken([
                    'tokenType' => $content['data']['tokenType'],
                    'accessToken' => $content['data']['accessToken'],
                    'refreshToken' => $content['data']['refreshToken'],
                    'expiredAt' => Carbon::now()->addMinutes(25),
                ]);
                $data = $content['data'];
            }
        } catch (GuzzleException $exception) {
            throw HotaiAuthException::invalidGetToken($exception);
        }

        /** login then remove and redirect */
        $this->sessionManager->setCodeVerifier('');
        $this->_cookieService->removeCookie(self::COOKIE_CODE_VERIFIER);

        return $data ?? [];
    }


    /**
     * @param string $code
     * @param string $memberSeq
     * @param string $codeVerifier
     * @return array|mixed
     * @throws HotaiAuthException
     * @throws LocalizedException|JsonException
     */
    public function getTokenForRestApi(string $code, string $memberSeq, string $codeVerifier)
    {
        $content = [
            'grantType'    => 'authorization_code',
            'clientId'     => $this->clientId,
            'codeVerifier' => $codeVerifier,
            'redirectUri'  => $this->apiRedirectUri,
            'code'         => $code,
            'clientSecret' => $this->clientSecret,
            'memberSeq'    => $memberSeq
        ];

        try {
            $response = $this->client->post("Connect/Token", [
                'headers' => [
                    'APP_ID'       => $this->appId,
                    'Content-Type' => self::CONTENT_TYPE
                ],
                'json'    => $content
            ]);

            $content = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            if($content['data']) {
                $content['data']['accessTokenEncrypted'] = $this->encryptToken($content['data']['accessToken']);
                return $content['data'];
            }

        } catch (GuzzleException $exception) {
            $message = $this->extractGuzzleException($exception);
            $this->writeLog('Hotai getTokenForRestApi exception:' . print_r($message, true));

            throw new HotaiAuthException(__($message));
        }

        return [];
    }

    /**
     * @param string $accessToken
     * @param string $refreshToken
     * @return mixed|string|string[]
     * @throws HotaiAuthException|JsonException
     */
    public function refreshToken(string $accessToken, string $refreshToken)
    {
        $content = [
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
        ];

        $this->hotaiAppAuthLogger->info('[HotaiAuthService] Refresh access_token:' . $accessToken . ', refresh_token:' . $refreshToken);
        try {
            $response = $this->client->post("Connect/RefreshToken", [
                'headers' => [
                    'APP_ID' => $this->appId,
                    'Content-Type' => self::CONTENT_TYPE
                ],
                'json' => $content
            ]);

            $content = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            if($content['data']) {
                $content['data']['accessTokenEncrypted'] = $this->encryptToken($content['data']['accessToken']);
                return $content['data'];
            }
        } catch (GuzzleException $exception) {

            $message = $this->extractGuzzleException($exception);

            $this->writeLog('Hotai refreshToken exception:' . print_r($message, true));
            $this->hotaiAppAuthLogger->error('[HotaiAuthService] Hotai refreshToken exception:' . print_r($message, true));

            throw new HotaiAuthException(__($message));
        }

        return [];
    }

    /**
     * 取得轉換子系統 Token (SSO)
     * 提供子系統為了將使用者轉到另一個平台，取得SSO所需Token
     *
     * @param string $accessToken 會員 Token
     * @param string|null $platform 欲前往的子系統
     * @return mixed|string|string[]
     * @throws FileSystemException
     * @throws HotaiAuthException
     * @throws JsonException
     * @throws Zend_Log_Exception
     */
    public function reacquireGetSSOToken(string $accessToken, string $platform = null): mixed
    {
        // set external source access token and external source platform to reacquire token
        $platform = $platform ?? $this->appId;

        $this->writeAppLog('Hotai reacquireGetSSOToken access_token:' . print_r($accessToken, true));
        $this->writeAppLog('Hotai reacquireGetSSOToken platform:' . print_r($platform, true));

        try {
            // platform 是要換成哪個目標平台的 token
            $payload = json_encode([
                'access_token' => $accessToken,
                'platform'     => $this->hotaiAppAppId,
            ], JSON_THROW_ON_ERROR);

            $this->writeAppLog('Hotai reacquireGetSSOToken request $payload:' . print_r($payload, true));

            $content = [
                'Body' => base64_encode(openssl_encrypt(
                    $payload,
                    'AES-256-CBC',
                    $this->hotaiWebAesKey,
                    OPENSSL_RAW_DATA,
                    $this->hotaiWebAesIv
                )),
                'Method' => 'POST',
                'Route' => 'api/token/sso',
            ];

            $requestConfig = [
                'headers' => [
                    'APP_ID' => $this->hotaiWebAppId,
                    'Content-Type' => self::CONTENT_TYPE,
                    'APP_VERSION' => $this->appVersionForWeb,
                    'API_VERSION' => $this->apiVersionForWeb,
                ],
                'json' => $content
            ];
            $this->writeAppLog('Hotai reacquireGetSSOToken request config (endpoint: api/token/sso):' . print_r($requestConfig, true));

            $response = $this->client->post("api/app/service/", $requestConfig);

            $responseContent = $response->getBody()->getContents();
            $this->writeAppLog('Hotai reacquireGetSSOToken response content:' . print_r($responseContent, true));

            $decryptedData = openssl_decrypt($responseContent, 'AES-256-CBC', $this->hotaiWebAesKey, 0, $this->hotaiWebAesIv);
            $decryptedText = mb_convert_encoding($decryptedData, "UTF-8", mb_detect_encoding($decryptedData));

            $result = json_decode($decryptedText, true, 512, JSON_THROW_ON_ERROR);
            $this->writeAppLog('Hotai reacquireGetSSOToken response content after:' . print_r($result, true));

            if (isset($result['token'])) {
                return [
                    'token' => $result['token']
                ];
            }

            throw new HotaiAuthException(__('Invalid response format from SSO API'));
        } catch (GuzzleException $exception) {
            $message = $this->extractGuzzleException($exception);
            $this->writeAppLog('Hotai reacquireGetSSOToken exception:' . print_r($message, true));
            $this->hotaiAppAuthLogger->error('[HotaiAuthService] Hotai reacquireGetSSOToken exception:' . print_r($message, true));
            throw new HotaiAuthException(__($message));
        }
    }

    /**
     * use the external source access token and external source platform to reacquire access token of this platform
     * @param string $accessToken
     * @param null $platform
     * @return mixed|string|string[]
     * @throws FileSystemException
     * @throws HotaiAuthException
     * @throws JsonException
     * @throws Zend_Log_Exception
     */
    public function reacquireToken(string $accessToken, $platform = null): mixed
    {
        // set external source access token and external source platform to reacquire a token
        // platform 是 來源的token 來自哪個平台
        $platform = $platform ?? $this->appId;
        $requestContent = [
            'token' => $accessToken,
            'platform' => $platform,
        ];

        /**
         * 當platform是HTGO的時候 appkey 跟 appid 換成 HTGOAPP
         */
        if ($platform === $this->hotaiWebAppId) {
            $appId = $this->hotaiAppAppId;
            $appKey = $this->hotaiScopeConfig->getAuthScopeConfig(
                HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_HOTAI_APP_APP_KEY
            );
        } else {
            $appId = $this->hotaiWebAppId;
            $appKey = $this->hotaiScopeConfig->getAuthScopeConfig(
                HotaiScopeConfig::HOTAI_AUTH_CONFIG_PATH_APP_KEY
            );
        }

        // the header is APP_ID and AppKey of this website /platform
        try {

            $requestBody = [
                // header 是要換成哪個目標平台的token 的APP_ID
                'headers' => [
                    'APP_ID' => $appId,
                    'AppKey' => $appKey
                ],
                'json' => $requestContent
            ];
            $this->writeAppLog('Hotai reacquireToken request body:' . print_r($requestBody, true));

            $response = $this->clientBack->post("api/token/sso/verify", $requestBody);
            $responseContent = $response->getBody()->getContents();
            $this->writeAppLog('Hotai reacquireToken response content:' . print_r($responseContent, true));

            $responseContent = json_decode($responseContent, true, 512, JSON_THROW_ON_ERROR);

            if (isset($responseContent['access_token'])) {
                $this->sessionManager->setHotaiToken([
                    'tokenType'             => $responseContent['token_type'],
                    'accessToken'           => $responseContent['access_token'],
                    'accessTokenEncrypted'  => $this->encryptToken($responseContent['access_token']),
                    'refreshToken'          => $responseContent['refresh_token'],
                    'expiredAt'             => Carbon::now()->addMinutes(25),
                ]);
                $data = $responseContent;
            }
        } catch (GuzzleException $exception) {
            $message = $this->extractGuzzleException($exception);
            $this->writeAppLog('Hotai reacquireToken exception:' . print_r($message, true));
            $this->hotaiAppAuthLogger->error('[HotaiAuthService] Hotai reacquireToken exception:' . print_r($message, true));
            throw new HotaiAuthException(__($message));
        }

        return $data ?? [];
    }

    /**
     * @return mixed|string|string[]
     * @throws HotaiAuthException|JsonException
     */
    public function getUserProfile($accessToken = null): mixed
    {
        if (is_null($accessToken)) {
            $accessToken = $this->sessionManager->getHotaiToken();
        }

        $this->writeLog('Hotai getUserProfile accessToken:' . print_r($accessToken, true));

        $content = [
            'Body' => '',
            'Method' => 'GET',
            'Route' => 'api/member/profile',
        ];

        try {
            $response = $this->client->post("api/app/service/", [
                'headers' => [
                    'APP_ID' => $this->appId,
                    'Content-Type' => self::CONTENT_TYPE,
                    'APP_VERSION' => $this->appVersion,
                    'API_VERSION' => $this->apiVersion,
                    'Authorization' => 'Bearer ' . $accessToken
                ],
                'json' => $content
            ]);

            $content = $response->getBody()->getContents();
            $this->writeLog('Hotai getUserProfile content before:' . print_r($content, true));
            if (empty($content)) {
                return [];
            }

            $decryptedData = openssl_decrypt($content, 'AES-256-CBC', $this->aesKey, 0, $this->aesIv);
            $decryptedText = mb_convert_encoding($decryptedData, "UTF-8", mb_detect_encoding($decryptedData));

            $result = json_decode($decryptedText, true, 512, JSON_THROW_ON_ERROR);
            $this->writeLog('Hotai getUserProfile content after:' . print_r($result, true));
            $result['name'] = $this->sanitizeMemberName($result['name']);

            $this->sessionManager->setHotaiProfile($result);

            return $result;
        } catch (GuzzleException $guzzleException) {
            $message = $this->extractGuzzleException($guzzleException);
            $this->writeLog('Hotai getUserProfile exception:' . print_r($message, true));
            throw new HotaiAuthException(__($message));
        } catch (Exception $exception) {
            $this->writeLog('Hotai getUserProfile exception:' . print_r($exception->getMessage(), true));
            throw HotaiAuthException::invalidGetUserProfile($exception);
        }
    }

    public function getHotaiOneId($oneId): string
    {
        $content = [
            'OneId' => $oneId,
            'Service' => $this->hotaiOneIdService,
        ];

        try {
            $response = $this->clientOneId->post("api/GetHash/GetOneiDHash", [
                'headers' => [
                    'Content-Type' => self::CONTENT_TYPE,
                ],
                'json' => $content
            ], ['timeout' => 5]);

            $content = $response->getBody()->getContents();
            $this->writeLog('Hotai setHotaiOneId content:' . print_r($content, true));
            $contentArray = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            if (count($contentArray) > 0 && $contentArray['ResultCode'] === 0) {
                return $contentArray['HashData'];
            }
        } catch (Exception $exception) {
            $this->writeLog('Hotai setHotaiOneId exception:' . print_r($exception->getMessage(), true));
        }
        return '';
    }

    /**
     * decrypt the token from external source
     * @return mixed|string|string[]
     * @throws HotaiAuthException
     */
    public function externalDecryptToken($accessToken, $platform = null): mixed
    {

        $this->writeLog('Hotai externalDecryptToken accessToken:' . print_r($accessToken, true));
        $this->writeLog('Hotai externalDecryptToken platform:' . print_r($platform, true));

        /** if is production mode, and an external source platform is not null, use the platform's aes key and iv */
        try {

            if ($this->hotaiExternExchangeIsProduction && $platform) {
                $aesKey = $this->hotaiExternExchangeAesKeyProduction[$platform] ?? '';
                $aesIv = $this->hotaiExternExchangeAesIvProduction[$platform] ?? '';
            } else {
                $aesKey = $this->hotaiExternExchangeAesKey;
                $aesIv = $this->hotaiExternExchangeAesIv;
            }
        } catch (Exception $exception) {
            throw HotaiAuthException::invalidExternalDecryptPlatform($exception);
        }

        /** decrypt the token */
        try {
            // first try base64 decode directly
            $t = base64_decode($accessToken);
            $decryptedData = openssl_decrypt(
                $t,
                'AES-256-CBC',
                $aesKey,
                OPENSSL_RAW_DATA,
                $aesIv
            );

            // in case the first decryption failed, try urldecode first
            if ($decryptedData === false) {
                $t = base64_decode(urldecode($accessToken));
                $decryptedData = openssl_decrypt(
                    $t,
                    'AES-256-CBC',
                    $aesKey,
                    OPENSSL_RAW_DATA,
                    $aesIv
                );
            }

            $this->writeLog('Hotai externalDecryptToken aeskey to decrypt:' . print_r($aesKey, true));
            $this->writeLog('Hotai externalDecryptToken aesiv to decrypt:' . print_r($aesIv, true));
            $this->writeLog('Hotai externalDecryptToken decryptedData:' . print_r($decryptedData, true));

            return $decryptedData;
        } catch (Exception $exception) {
            throw HotaiAuthException::invalidExternalDecryptToken($exception);
        }
    }

    /**
     * @return false|string
     * @throws HotaiAuthException
     */
    public function externalEncryptToken(): false|string
    {
        $accessToken = $this->sessionManager->getHotaiToken();
        if (empty($accessToken)) {
            return '';
        }
        try {
            $this->writeLog('Hotai externalEncryptToken accessToken:' . print_r($accessToken, true));

            $encryptData = openssl_encrypt(
                $accessToken,
                'AES-256-CBC',
                $this->aesKey,
                OPENSSL_RAW_DATA,
                $this->aesIv
            );

            $this->writeLog('Hotai externalEncryptToken openssl_encrypt:' . print_r($encryptData, true));
            return urlencode(base64_encode($encryptData));
        } catch (Exception $exception) {
            throw HotaiAuthException::invalidExternalEncryptToken($exception);
        }
    }


    /**
     * @return false|string
     * @throws HotaiAuthException
     */
    public function encryptToken($accessToken): false|string
    {
        if (empty($accessToken)) {
            return '';
        }
        try {
            $this->writeLog('Hotai encryptToken accessToken:' . print_r($accessToken, true));

            $encryptData = openssl_encrypt(
                $accessToken,
                'AES-256-CBC',
                $this->aesKey,
                OPENSSL_RAW_DATA,
                $this->aesIv
            );

            //$this->writeLog('Hotai encryptToken openssl_encrypt:' . print_r($encryptData, true));
            return urlencode(base64_encode($encryptData));
        } catch (Exception $exception) {
            throw HotaiAuthException::invalidExternalEncryptToken($exception);
        }
    }

    /**
     * @return array
     */
    public function hotaiGoTravel(): array
    {
        $accessToken = $this->sessionManager->getHotaiToken();

        if (empty($accessToken)) {
            return [];
        }

        $aes_key = base64_decode($this->hotaiGoTravelAesKey);
        $aes_iv = base64_decode($this->hotaiGoTravelAesIv);
        $encrypted_token = openssl_encrypt(
            $accessToken,
            'AES-256-CBC',
            $aes_key,
            OPENSSL_RAW_DATA,
            $aes_iv
        );

        return [
            'url' => $this->hotaiGoTravelApiDomain,
            'appsrc' => $this->hotaiGoTravelAppId,
            'token' => base64_encode($encrypted_token)
        ];
    }

    /**
     * @throws Exception
     */
    private function codeChallenge(): string
    {
        $refererUrl = $this->decodeRefererUrl($this->_redirect->getRefererUrl());
        $currentUrl = $this->url->getCurrentUrl();

        // 加上 log 紀錄 refererUrl 和 currentUrl
        $this->writeLog('Hotai codeChallenge refererUrl: ' . print_r($refererUrl, true));
        $this->writeLog('Hotai codeChallenge currentUrl: ' . print_r($currentUrl, true));

        // 如果 refererUrl 是首頁或空的，嘗試從 currentUrl 提取
        if (empty($refererUrl) || $this->isHomePage($refererUrl)) {
            $extractedUrl = $this->extractRefererFromCurrentUrl($currentUrl);
            if (!empty($extractedUrl)) {
                $refererUrl = $extractedUrl;
            }
        }

        if (($refererUrl && strpos($refererUrl, 'magetop_quickview') !== false)
            || ($currentUrl && strpos($currentUrl, 'checkout/cart/add') !== false)) {
            $refererUrl = $this->url->getUrl('checkout/index');
        }

        $codeVerifier = $this->randomStr(43);
        $this->sessionManager->setCodeVerifier($codeVerifier);
        $this->sessionManager->setCustomerRefererUrl($refererUrl);
        $this->_cookieService->setCookie(self::COOKIE_CODE_VERIFIER, $codeVerifier, 1800);
        $this->_cookieService->setCookie(self::COOKIE_REDIRECT_URL, $refererUrl, 1800);

        $this->writeLog('Hotai CodeVerifier info:' . print_r($codeVerifier, true));
        $this->writeLog('Hotai CodeChallenge info:' . print_r(hash('sha256', $codeVerifier), true));
        return hash('sha256', $codeVerifier);
    }

    /**
     * 檢查是否為首頁 URL
     * @param string $url
     * @return bool
     */
    private function isHomePage(string $url): bool
    {
        if (empty($url)) {
            return true;
        }

        // 移除協議和域名，只保留路徑
        $parsedUrl = parse_url($url);
        $path = $parsedUrl['path'] ?? '/';

        // 如果路徑為根目錄或空，則為首頁
        return $path === '/' || $path === '';
    }

    /**
     * 從 current URL 中提取 referer 參數
     * @param string $currentUrl
     * @return string
     */
    private function extractRefererFromCurrentUrl(string $currentUrl): string
    {
        // 檢查 URL 是否包含 /referer/ 路徑
        if (strpos($currentUrl, '/referer/') === false) {
            return '';
        }

        // 提取 referer 參數 (base64 編碼的部分)
        $parts = explode('/referer/', $currentUrl);
        if (count($parts) < 2) {
            return '';
        }

        // 取得 base64 編碼的 referer 部分，移除尾部的 /
        $encodedReferer = trim($parts[1], '/');

        // 移除可能的查詢參數
        $encodedReferer = explode('?', $encodedReferer)[0];

        try {
            // base64 解碼
            $decodedReferer = base64_decode($encodedReferer);

            // 驗證解碼結果是否為有效 URL
            if ($decodedReferer && filter_var($decodedReferer, FILTER_VALIDATE_URL)) {
                return $decodedReferer;
            }
        } catch (Exception $e) {
            // 解碼失敗，返回空字串
        }

        return '';
    }

    /**
     * @param string $refererUrl
     * @return string
     */
    private function decodeRefererUrl(string $refererUrl): string
    {
        $parsedUrl = parse_url($refererUrl);
        if (!isset($parsedUrl['query'])) {
            return $refererUrl; // No query string
        }
        parse_str($parsedUrl['query'], $queryParams);
        if (empty($queryParams['back_url'])) {
            return $refererUrl; // vip_url missing or empty
        }
        $decoded = $this->decoder->decode($queryParams['back_url']);
        if ($decoded) {
            $refererUrl = $decoded; // Invalid base64
        }
        return $refererUrl;
    }

    /**
     * @param int $length
     * @return string
     * @throws Exception
     */
    private function randomStr(int $length): string
    {
        $result = '';
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~';
        for ($i = 0; $i < $length; $i++) {
            $randomChar = $chars[random_int(0, strlen($chars) - 1)];
            $result .= $randomChar;
        }
        return $result;
    }

    /**
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     */
    public function writeLog($message, $folderName = 'hotai_auth_service'): void
    {
        $this->_loggerInterface->writeLog($message, $folderName);
    }

    /**
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     */
    public function writeAppLog($message, $folderName = 'hotai_auth_app_service'): void
    {
        $this->_loggerInterface->writeLog($message, $folderName);
    }

    /**
     * sanitizeMemberName
     *
     * @param  string $name
     * @return string
     */
    public function sanitizeMemberName($name) {
        $output = '';
        $length = mb_strlen($name, 'UTF-8');

        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($name, $i, 1, 'UTF-8');
            $codepoint = \IntlChar::ord($char);

            if (
                // 中文區塊
                ($codepoint >= 0x4E00 && $codepoint <= 0x9FFF) ||  // 基本漢字
                ($codepoint >= 0x3400 && $codepoint <= 0x4DBF) ||  // 擴展A
                ($codepoint >= 0x20000 && $codepoint <= 0x2A6DF) ||  // 擴展B
                ($codepoint >= 0x2A700 && $codepoint <= 0x2B73F) ||  // 擴展C
                ($codepoint >= 0x2B740 && $codepoint <= 0x2B81F) ||  // 擴展D
                ($codepoint >= 0x2B820 && $codepoint <= 0x2CEAF) ||  // 擴展E
                // 英文字母
                ($codepoint >= 0x41 && $codepoint <= 0x5A) ||     // A-Z
                ($codepoint >= 0x61 && $codepoint <= 0x7A) ||     // a-z
                // 數字
                ($codepoint >= 0x30 && $codepoint <= 0x39)        // 0-9
            ) {
                $output .= $char;
            } else {
                $output .= 'O';
                $this->writeLog('Hotai getUserProfile content after First name contains invalid characters:' . print_r($name, true));
            }
        }

        return $output;
    }

    public function extractGuzzleException(GuzzleException $exception): string
    {
        if (method_exists($exception, 'getResponse')) {
            $content = $exception->getResponse()?->getBody()?->getContents();
            if ($content) {
                $errorInfo = json_decode($content, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    return $content;
                } else {
                    if (isset($errorInfo['detail'])) {
                        return $errorInfo['detail'];
                    } elseif (!empty($errorInfo['errors'])) {
                        $messages = [];
                        foreach ($errorInfo['errors'] as $error) {
                            if (is_array($error)) {
                                $messages = array_merge($messages, array_values($error));
                            } elseif (is_string($error)) {
                                $messages[] = $error;
                            }
                        }

                        return implode(', ', $messages);
                    }

                    return $content;
                }
            }
        }


        return $exception->getMessage();
    }

    public function getHotaiOneIdByPhoneNumber(string $phoneNumber): ?string
    {
        try {
            $requestBody = [
                'headers' => [
                    'APP_ID' => $this->appId,
                    'AppKey' => $this->hotaiExternExchangeAppKey
                ]
            ];

            $response = $this->clientBack->get("api/subsystem/member/MobilePhoneToOneID/{$phoneNumber}", $requestBody);

            $content = $response->getBody()->getContents();
            $contentArray = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            return $contentArray['memberSeq'] ?? null;
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            $this->writeLog('Error calling MobilePhoneToOneID API: ' . $this->extractGuzzleException($e));
            return null;
        } catch (\JsonException $e) {
            $this->writeLog('JSON decoding error in getHotaiOneIdByPhoneNumber: ' . $e->getMessage());
            return null;
        }
    }

    public function getListOneIdByPhoneNumber(Array $phoneNumbers){
        $this->writeLog('Get List account by Phone number:' . print_r($phoneNumbers, true));

        try {

            $requestBody = [
                // header 是要換成哪個目標平台的token 的APP_ID
                'headers' => [
                    'APP_ID' => $this->appId,
                    'AppKey' => $this->hotaiExternExchangeAppKey
                ],
                'json' => [
                    'mobilePhones' => $phoneNumbers
                ]
            ];
            $this->writeLog('Get List account request body:' . print_r($requestBody, true));

            $response = $this->clientBack->post("api/subsystem/search-member", $requestBody);
            $responseContent = $response->getBody()->getContents();
            $this->writeLog('Get List account response content:' . print_r($responseContent, true));

            $data = json_decode($responseContent, true, 512, JSON_THROW_ON_ERROR);
            return is_array($data) ? $data : [];

        } catch (GuzzleException $exception) {
            $message = $this->extractGuzzleException($exception);
            $this->writeLog('Get List account exception:' . print_r($message, true));
            $this->hotaiAppAuthLogger->error('[HotaiAuthService] Get List account exception:' . print_r($message, true));
            throw new HotaiAuthException(__($message));
        }catch (\JsonException $e) {
            $this->writeLog('JSON decoding error in getListOneIdByPhoneNumber: ' . $e->getMessage());

        }

        return [];
    }
}
