<?php

namespace Branch8\Checkout\Observer;

use Branch8\HotaiAuth\Service\HotaiAuthService;
use Exception;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\Session\SessionManager;
use Magento\Framework\UrlInterface;
use \Magento\Framework\Message\ManagerInterface;
use \Magento\Framework\App\ResponseFactory;

class ValidateHotaiToken implements \Magento\Framework\Event\ObserverInterface
{

    protected $sessionManager;
    protected $hotaiAuthService;
    protected $actionFlag;
    protected $_urlInterface;
    protected $b8CustomerHelper;
    protected $_messageManager;
    protected $responseFactory;

    public function __construct(
        SessionManager $sessionManager,
        HotaiAuthService $hotaiAuthService,
        ActionFlag $actionFlag,
        UrlInterface $urlInterface,
        ManagerInterface $messageManager,
        ResponseFactory $responseFactory
    ) {
        $this->hotaiAuthService = $hotaiAuthService;
        $this->sessionManager = $sessionManager;
        $this->actionFlag = $actionFlag;
        $this->_urlInterface = $urlInterface;
        $this->_messageManager = $messageManager;
        $this->responseFactory = $responseFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {return;
        try {

            $hotaiToken = isset($this->sessionManager->getData()['hotai_token']) ?? '';

            if (!$hotaiToken) {
                $loginUrl = $this->hotaiAuthService->getLoginUrl();
                $this->actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);

                $cartUrl = $this->_urlInterface->getUrl($loginUrl);
                $this->sessionManager->setCustomerRefererUrl($this->_urlInterface->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true]));
                $this->responseFactory->create()->setRedirect($cartUrl);
                exit(0);
            }
        } catch (Exception $e) {
            $message = __('Please Relogin / Login With Hotai Panel.');
            $this->redirectToCart($observer, $message);
        }
    }

    /**
     * redirectToCart
     *
     * @param  \Magento\Framework\Event\Observer $observer
     * @param  string $message
     */
    protected function redirectToCart($observer, $message){
        $this->_messageManager->addErrorMessage($message);
        $customRedirectionUrl = $this->_urlInterface->getUrl('checkout/cart');
        $this->actionFlag->set('', \Magento\Framework\App\Action\Action::FLAG_NO_DISPATCH, true);
        $this->responseFactory->create()->setRedirect($customRedirectionUrl);
        exit(0);
    }

}
