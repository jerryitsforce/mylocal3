<?php

namespace Branch8\HotaiAuth\Controller\LoginWithToken;

use Branch8\HotaiAuth\Service\HotaiAuthService;
use Branch8\HotaiCore\Model\Detection\MobileDetect;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;

class Index extends Action
{
    protected $allowedAdditionalParams = [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content'
    ];

    /**
     * @var HotaiAuthService
     */
    protected $hotaiAuthService;
    /**
     * @var FormKey
     */
    protected $formKey;
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    /**
     * @var RequestInterface
     */
    protected $request;
    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;
    private MobileDetect $mobileDetect;

    /**
     * @var \Magento\Framework\View\Page\FaviconInterface
     */
    protected $favicon;

    /**
     * @var \Magento\Framework\View\Asset\Repository
     */
    private $assetRepository;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param RequestInterface $request
     * @param RedirectFactory $resultRedirectFactory
     * @param FormKey $formKey
     * @param HotaiAuthService $hotaiAuthService
     * @param \Magento\Framework\View\Asset\Repository $assetRepository
     * @param \Magento\Framework\View\Page\FaviconInterface $favicon
     * @throws LocalizedException
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        RequestInterface $request,
        RedirectFactory $resultRedirectFactory,
        FormKey $formKey,
        HotaiAuthService $hotaiAuthService,
        \Magento\Framework\View\Asset\Repository $assetRepository,
        \Magento\Framework\View\Page\FaviconInterface $favicon,
        MobileDetect $mobileDetect
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->request = $request;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->formKey = $formKey;
        $this->request->setParam('form_key', $this->formKey->getFormKey());
        $this->hotaiAuthService = $hotaiAuthService;
        $this->assetRepository = $assetRepository;
        $this->favicon = $favicon;
        $this->mobileDetect = $mobileDetect;
    }

    /**
     * @return ResponseInterface|Redirect|ResultInterface|Page
     */
    public function execute()
    {
        // 取得現有的 query string$
        $queryParams = $this->request->getParams();
        $logMessages[] = 'Hotai LoginWithToken POST request querystring params:' . print_r($queryParams, true);

        // 取得 POST 參數（form-data）
        $postData = $this->request->getPostValue();
        $logMessages[] = 'Hotai LoginWithToken POST data:' . print_r($postData, true);

        // 如果 getParam 抓不到參數，則從 content 中解析 JSON body 獲取
        if (empty($postData)) {
            $content = json_decode($this->request->getContent(), true);
            $logMessages[] = 'Hotai LoginWithToken POST body json content:' . print_r($content, true);
            if (is_array($content)) {
                $postData = $content;
            }
        }

        // 合併 query string 與 POST
        $postData = is_array($postData) ? $postData : [];
        $mergedParams = array_merge($queryParams, $postData);
        $logMessages[] = 'Hotai LoginWithToken POST request params merged with querystring:' . print_r($mergedParams, true);

        // 產生新的 query string
        $newQueryString = http_build_query($mergedParams);
        $logMessages[] = 'Hotai LoginWithToken POST to GET with new querystring: ' . print_r($newQueryString, true);

        // 收集參數並轉為 query string，用於js轉跳
        $additionalParamsString = $this->collectAdditionalParams($mergedParams);
        $logMessages[] = 'Hotai LoginWithToken POST to GET with new querystring additionalParams: ' . print_r($this->collectAdditionalParams($mergedParams), true);
        //$additionalParamsString = '';

        // 統一輸出 Log
        $this->hotaiAuthService->writeLog(implode("\n", $logMessages));

        // 顯示loading頁面，並將 queryString 和 additionalParams 傳遞給 JavaScript
        $faviconFile = $this->prepareFaviconFile();
        $html = $this->generateHtmlWithQueryString($newQueryString, $additionalParamsString, $faviconFile);
        $response = $this->getResponse();
        $response->setHeader('Content-Type', 'text/html', true)
            ->setHeader(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains',
                true
            );
        $response->setBody($html);

        return $response;
    }

    /**
     * @return string
     */
    public function prepareFaviconFile()
    {
        $faviconFile = $this->favicon->getFaviconFile();
        if ($faviconFile) {
            return $faviconFile;
        }
        $params = array('_secure' => $this->request->isSecure());
        return $this->assetRepository->getUrlWithParams($this->favicon->getDefaultFavicon(), $params);
    }

    private function collectAdditionalParams($params)
    {
        $additionalParams = [];
        foreach ($this->allowedAdditionalParams as $param) {
            if (isset($params[$param]) && $params[$param] !== '') {
                $additionalParams[$param] = $params[$param];
            }
        }
        // 直接轉換為查詢字串格式
        return !empty($additionalParams) ? http_build_query($additionalParams) : '';
    }

    private function generateHtmlWithQueryString($queryString, $additionalParamsString, $faviconFile)
    {
        $loader = <<<HTML
            <div class="dots">
                <span style="display:inline-block;animation:b 0.6s infinite ease-in-out">•</span>
                <span style="display:inline-block;animation:b 0.6s infinite ease-in-out;animation-delay:0.1s">•</span>
                <span style="display:inline-block;animation:b 0.6s infinite ease-in-out;animation-delay:0.2s">•</span>
            </div>
        HTML;

        //HotaiApp uses its own loader
        $loader = $this->mobileDetect->isHotaiApp() ? '' : $loader;

        return <<<HTML
        <!DOCTYPE html>
        <html lang="zh-TW">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>登入中...</title>
            <link  rel="icon" type="image/x-icon" href="{$faviconFile}" />
            <link  rel="shortcut icon" type="image/x-icon" href="{$faviconFile}" />
        </head>
        <body>
            <div style="margin:0;height:100vh;display:flex;align-items:center;justify-content:center;font-size:32px;color:#c5171e">
            <div style="text-align: center;">
            <svg xmlns="http://www.w3.org/2000/svg" width="138" height="31" viewBox="0 0 138 31" fill="none">
                    <g clip-path="url(#clip0_1862_14269)">
                    <path d="M15.571 30.0062V19.0672H5.537V30.0062H1.00049V6.09229H5.537V16.9576H15.571V6.09229H20.1075V30.0062H15.571Z" fill="#c5171e"/>
                    <path d="M63.1395 8.2356V29.9349H58.6924V8.2356H50.7568V6.09473H71.0795V8.2356H63.1395Z" fill="#c5171e"/>
                    <path d="M90.0908 29.9349L87.9187 23.9592H77.3081L74.9103 29.9349H72.5459L82.0055 6.09473H86.1577L94.9782 29.9349H90.0908ZM82.8056 10.1865L78.1707 21.787H87.0225L82.8033 10.1865H82.8056Z" fill="#c5171e"/>
                    <path d="M106.397 6.09473H101.861V29.9349H106.397V6.09473Z" fill="#c5171e"/>
                    <path d="M136.466 22.3324V23.9906V25.5102H137.364V17.9546H134.327H132.448H131.44V19.0049H134.653H136.466V20.8553V22.3324Z" fill="#c5171e"/>
                    <path d="M124.23 27.3623H122.852L123.216 30.006H124.23V27.3623Z" fill="#c5171e"/>
                    <path d="M129.471 23.9907H126.095V25.5103H129.471V23.9907Z" fill="#c5171e"/>
                    <path d="M120.852 10.5601H117.719V13.4138H120.852V10.5601Z" fill="#c5171e"/>
                    <path d="M120.852 15.6167H117.719V18.0526H120.852V15.6167Z" fill="#c5171e"/>
                    <path d="M120.852 20.1465H117.719V22.674H120.852V20.1465Z" fill="#c5171e"/>
                    <path d="M137.364 27.3623H136.466V30.006H137.364V27.3623Z" fill="#c5171e"/>
                    <path d="M134.656 23.9907H131.442V25.5103H134.656V23.9907Z" fill="#c5171e"/>
                    <path d="M134.656 20.855H131.442V22.3321H134.656V20.855Z" fill="#c5171e"/>
                    <path d="M134.327 11.5542V12.8146H136.62V14.6672H134.327V16.1041H137.364V11.5542H134.327Z" fill="#c5171e"/>
                    <path d="M132.448 11.5542H128.508V12.8146H132.448V11.5542Z" fill="#c5171e"/>
                    <path d="M132.448 14.667H128.508V16.1039H132.448V14.667Z" fill="#c5171e"/>
                    <path d="M134.654 27.3623H126.095V30.006H134.654V27.3623Z" fill="#c5171e"/>
                    <path d="M129.471 20.855H126.095V22.3321H129.471V20.855Z" fill="#c5171e"/>
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M132.519 6.09261H131.717V6.09452H131.718V9.70586H128.508V8.14377H126.676V9.70586H123.818V11.5562H126.676V12.8166H124.531V14.667H126.676V16.1039H123.818V17.9543H129.474V19.0046H124.231V25.5099H119.934L122.341 30.004H115.965L118.372 25.5099H115.844L115.846 25.5121L115.091 30.0062H114.523V6.09452H120.155C120.152 5.20504 120.32 3.62754 121.413 2.36283C122.465 1.14267 124.126 0.525879 126.271 0.525879C128.416 0.525879 130.054 1.14267 131.134 2.3606C132.236 3.60311 132.484 5.15625 132.519 6.09261ZM120.155 6.09484V8.43429H115.86V22.674V24.9311H122.72V8.43429H120.957V6.09452H131.717C131.679 5.27657 131.456 3.92925 130.528 2.89023C129.605 1.85555 128.2 1.33038 126.271 1.33038C124.342 1.33038 122.912 1.85555 122.021 2.888C121.099 3.95501 120.957 5.32972 120.957 6.09229H120.957V6.09452H120.957C120.957 6.09463 120.957 6.09474 120.957 6.09484H120.155ZM132.52 8.14377V6.09452H137.365V9.70586H134.328V8.14377H132.52Z" fill="#c5171e"/>
                    <path d="M45.8046 6.09208H42.3876C42.2938 4.26854 40.8971 0.556641 37.0712 0.556641C35.7326 0.556641 34.5817 1.03934 33.6476 1.98911C32.3626 3.29866 31.871 5.13562 31.8442 6.09208H28.4496L27.4663 30.0038H46.7901L45.8068 6.09208H45.8046ZM34.1772 2.5098C34.9661 1.7053 35.9382 1.29857 37.0712 1.29857C40.3451 1.29857 41.5519 4.49425 41.6457 6.09432H32.5861C32.6151 5.2116 33.1023 3.60482 34.1772 2.5098ZM30.2039 27.3735L30.9726 8.72237H31.8598C31.8665 9.64085 31.8598 10.4655 31.8598 10.4833L32.6017 10.4789C32.6017 10.4074 32.6062 9.60956 32.6017 8.72237H41.6547C41.6547 9.67884 41.6547 10.5481 41.6547 10.566H42.3966C42.3966 10.4923 42.3966 9.64978 42.3966 8.72237H43.2793L44.0481 27.3735H30.2039Z" fill="#c5171e"/>
                    </g>
                    <defs>
                    <clipPath id="clip0_1862_14269">
                    <rect width="138" height="30" fill="#c5171e" transform="translate(0 0.525879)"/>
                    </clipPath>
                    </defs>
                </svg>
                <br />
                <div style="min-height: 45px;">
                    $loader
                </div>
            </div>
            </div>

            <style>
                @keyframes b {
                    0%, 100% { transform: translateY(0); opacity: 1; }
                    50%      { transform: translateY(-8px); opacity: 0.6; }
                }
            </style>
            <script>
                const ua = navigator.userAgent;
                const isHotaiApp = /HotaiApp/i.test(ua);
                const queryString = window.location.search;
                const additionalParamsString = "$additionalParamsString";
                window.ReactNativeWebView?.postMessage(JSON.stringify({
                    type: 'LOGIN_API_CALL_START',
                    data: ''
                }));
                fetch(`/rest/V1/hotai_auth/loginWithToken\${queryString}`, {
                    method: 'GET',
                    headers: { 
                        'Content-Type': 'application/json',
                        'Strict-Transport-Security': 'max-age=31536000; includeSubDomains; preload'
                    }
                })
                .then(response => response.json())
                .then(response => {
                    //For HOTAI APP
                     window.ReactNativeWebView?.postMessage(JSON.stringify({
                        type: 'LOGIN_SUCCESS',
                        data: JSON.stringify(response)
                    }));
                    if (!isHotaiApp) {
                        if(response?.redirect_url) {
                            // Fix for Client DOM XSS: Use DOM anchor for URL validation
                            // Pattern verified by Checkmarx - Reference: javascript-xss.md Skill
                            var redirectUrl = response.redirect_url;
                            var anchor = document.createElement('a');
                            anchor.href = redirectUrl;
                            
                            // Validate: only allow http/https or same-origin relative paths
                            var isSafe = (anchor.protocol === 'http:' || anchor.protocol === 'https:');
                            var isRelative = redirectUrl.startsWith('/') && !redirectUrl.startsWith('//');
                            
                            if (isSafe || isRelative) {
                                window.location.href = anchor.href;
                            } else {
                                console.error('Invalid redirect URL: unsafe scheme');
                                window.location.href = additionalParamsString === '' ? '/' : '/' + '?' + additionalParamsString;
                            }
                        } else {

                            window.location.href = additionalParamsString === '' ? '/' : '/' + '?' + additionalParamsString;
                        }
                    }

                    console.log('Login successful:', JSON.stringify(response));
                })
                .catch(error => {
                     window.ReactNativeWebView?.postMessage(JSON.stringify({
                        type: 'LOGIN_FAILED',
                        data: '1'
                    }));
                    console.error('Login failed:', error);
                    window.location.href = additionalParamsString === '' ? '/' : '/' + '?' + additionalParamsString;
                })
                .finally(() => {
                    //For HOTAI APP
                    window.ReactNativeWebView?.postMessage(JSON.stringify({
                        type: 'LOGIN_API_CALL_END',
                        data: ''
                    }));
                });
            </script>
        </body>
        </html>
        HTML;
    }
}
