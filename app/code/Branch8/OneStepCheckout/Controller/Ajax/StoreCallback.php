<?php

namespace Branch8\OneStepCheckout\Controller\Ajax;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\Asset\Repository;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\App\RequestInterface;

class StoreCallback extends Action
{
    /**
     * @var RawFactory
     */
    protected $resultRawFactory;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $session;
    /**
     * @var Session
     */
    protected $checkoutSession;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;
    /**
     * @var Repository
     */
    protected $viewFileUrl;
    /**
     * @var FormKey
     */
    protected $formKey;
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @param Context $context
     * @param RawFactory $resultRawFactory
     * @param \Magento\Customer\Model\Session $session
     * @param Session $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param JsonFactory $resultJsonFactory
     * @param Repository $viewFileUrl
     * @param FormKey $formKey
     * @param RequestInterface $request
     */
    public function __construct(
        Context $context,
        RawFactory $resultRawFactory,
        \Magento\Customer\Model\Session $session,
        \Magento\Checkout\Model\Session $checkoutSession,
        StoreManagerInterface $storeManager,
        JsonFactory $resultJsonFactory,
        Repository $viewFileUrl,
        FormKey $formKey,
        RequestInterface $request
    ) {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
        $this->session = $session;
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->viewFileUrl = $viewFileUrl;
        $this->request = $request;
        $this->formKey = $formKey;
        $this->request->setParam('form_key', $this->formKey->getFormKey());
    }

    public function execute()
    {
        $resultRaw = $this->resultRawFactory->create();
        $resultJson = $this->resultJsonFactory->create();
        $baseUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
        $type = $this->request->getParam('type')?? 'address';
        $redirectUrl = urldecode($this->request->getParam('redirect_url')?? ($type === 'checkout' ? $baseUrl.'checkout' : $baseUrl.'customer/address/new/type/convenience_store'));
        // $aaa = $type === 'checkout' ? $baseUrl.'checkout' : $baseUrl.'customer/address/new/type/convenience_store';
        $loadingImg = $this->viewFileUrl->getUrl('Branch8_OneStepCheckout::images/page-loading.gif');

        if ($this->getRequest()->isPost()) {
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/store_address_redirect.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info('Base URL : ' . $baseUrl);

            $postData = $this->getRequest()->getPostValue();
            $postDataJson = json_encode($postData);
            $logger->info('Data : ' . json_encode($postData));
            $logger->info('Type : ' . $type);
            $logger->info('redirectUrl : ' . $redirectUrl);
            $html = '';
            if($type === 'checkout') {
                $this->session->setStoreCheckoutData($postDataJson);
                $html = <<<HTML
                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                    <img src="$loadingImg" alt="loading" width="80px" alt="Loading...">
                </div>
                <script>
                    window.location.href = '$redirectUrl';
                </script>
            HTML;
            } else {
                $this->session->setStoreData($postDataJson);
            $html = <<<HTML
                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                    <img src="$loadingImg" alt="loading" width="80px" alt="Loading...">
                </div>
                <script>
                    window.location.href = '$redirectUrl';
                </script>
            HTML;
            }

            $resultRaw->setContents($html);
            return $resultRaw;
        }

        // If not a POST request, return an error message
        return $resultJson->setData([
            'success' => false,
            'message' => 'Invalid Request'
        ]);
    }
}
