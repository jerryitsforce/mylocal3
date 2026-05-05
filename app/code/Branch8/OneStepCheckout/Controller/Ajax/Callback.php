<?php

namespace Branch8\OneStepCheckout\Controller\Ajax;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Store\Model\StoreManagerInterface;

class Callback extends Action
{
    /**
     * @var RawFactory
     */
    protected $resultRawFactory;
    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
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
     * @param Context $context
     * @param RawFactory $resultRawFactory
     * @param \Magento\Framework\Session\SessionManagerInterface $session
     * @param Session $checkoutSession
     * @param StoreManagerInterface $storeManager
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        RawFactory $resultRawFactory,
        \Magento\Framework\Session\SessionManagerInterface $session,
        \Magento\Checkout\Model\Session $checkoutSession,
        StoreManagerInterface $storeManager,
        JsonFactory $resultJsonFactory,
    ) {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
        $this->session = $session;
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    public function execute()
    {
        $resultRaw = $this->resultRawFactory->create();
        $resultJson = $this->resultJsonFactory->create();
        $baseUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);

        if ($this->getRequest()->isPost()) {
            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/store_address_redirect.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info('Base URL : ' . $baseUrl);

            $postData = $this->getRequest()->getPostValue();
            $logger->info('Data : ' . json_encode($postData));
            $resultRaw->setContents(
                "<script>
                    window.opener.postMessage(". json_encode($postData) .", '".$baseUrl."');
                    window.close();
                </script>"
            );
            return $resultRaw;
        }

        // If not a POST request, return an error message
        return $resultJson->setData([
            'success' => false,
            'message' => 'Invalid Request'
        ]);
    }
}