<?php
namespace Branch8\OneStepCheckout\Controller\Ajax;

use Branch8\HotaiAuth\Service\HotaiAuthService;
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
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;

class SelectStores extends Action
{
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
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;
    /**
     * @var Repository
     */
    protected $viewFileUrl;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param RequestInterface $request
     * @param RedirectFactory $resultRedirectFactory
     * @param FormKey $formKey
     * @param HotaiAuthService $hotaiAuthService
     * @param UrlInterface $urlBuilder
     * @param Repository $viewFileUrl
     * @throws LocalizedException
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        RequestInterface $request,
        RedirectFactory $resultRedirectFactory,
        FormKey $formKey,
        HotaiAuthService $hotaiAuthService,
        UrlInterface $urlBuilder,
        Repository $viewFileUrl
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->request = $request;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->formKey = $formKey;
        $this->request->setParam('form_key', $this->formKey->getFormKey());
        $this->hotaiAuthService = $hotaiAuthService;
        $this->urlBuilder = $urlBuilder;
        $this->viewFileUrl = $viewFileUrl;
    }

    /**
     * @return ResponseInterface|Redirect|ResultInterface|Page
     */
    public function execute()
    {
        $queryParams = $this->getRequest()->getParams();
        $type = $this->request->getParam('type')?? 'address';
        $redirectUrl = urldecode($this->request->getParam('redirect_url')?? '');
        $loadingImg = $this->viewFileUrl->getUrl('Branch8_OneStepCheckout::images/page-loading.gif');
        $formKey = $this->formKey->getFormKey();

        $url = $this->urlBuilder->getUrl('checkout/ajax/storeCallback'). '?type='.$type.'&redirect_url=' . urlencode($redirectUrl);
        $html = <<<HTML
        <!DOCTYPE html>
        <html lang="zh-TW">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>登入中...</title>
        </head>
        <body class="select-stores" style="padding: 0; margin: 0; position: relative; height: 100vh; width: 100%;">
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                <img src="$loadingImg" alt="loading" width="80px" alt="Loading...">
            </div>
            <form id="form-store-submit" action="https://emap.presco.com.tw/emapmobileu.ashx" method="POST" style="display: none;">
                <input type="hidden" name="form_key" value="$formKey">
                <input type="hidden" id="eshopid" name="eshopid" value="234">
                <br>
                <input type="hidden" id="servicetype" name="servicetype" value="3">
                <br>
                <input type="hidden" id="url" name="url" value="$url">
                <br>
                <input type="hidden" id="tempvar" name="tempvar" value="checkout">
                <br>
                <input type="hidden" id="storeid" name="storeid" value="">
                <!-- <br>     -->
                <!-- <button type="submit">OK</button> -->
            </form>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('form-store-submit').submit();
            });
        </script>
        </body>
        </html>
        HTML;

        // GET 請求時直接顯示頁面
        $response = $this->getResponse();
        $response->setHeader('Content-Type', 'text/html', true);
        $response->setBody($html);

        return $response;
    }
}
