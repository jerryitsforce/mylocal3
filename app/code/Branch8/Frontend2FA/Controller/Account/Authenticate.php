<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

// @codingStandardsIgnoreFile

namespace Branch8\Frontend2FA\Controller\Account;

use Branch8\Frontend2FA\Model\SecretFactory;
use Magento\Framework\App\RequestInterface;

class Authenticate extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Branch8\Frontend2FA\Model\GoogleAuthenticatorService
     */
    public $googleAuthenticator;
    /**
     * @var SecretFactory
     */
    public $secretFactory;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    protected $_url;

    /**
     * @param \Magento\Framework\App\Action\Context                      $context
     * @param \Magento\Customer\Model\Session                            $customerSession
     * @param \Branch8\Frontend2FA\Model\GoogleAuthenticatorService $googleAuthenticator
     * @param SecretFactory                                              $secretFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Branch8\Frontend2FA\Model\GoogleAuthenticatorService $googleAuthenticator,
        SecretFactory $secretFactory,
        \Magento\Framework\UrlInterface $url
    ) {
        $this->_customerSession = $customerSession;
        parent::__construct($context);
        $this->googleAuthenticator = $googleAuthenticator;
        $this->secretFactory = $secretFactory;
        $this->_url = $url;
    }

    /**
     * @param RequestInterface $request
     *
     * @throws \Magento\Framework\Exception\NotFoundException
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function dispatch(RequestInterface $request)
    {
        $loginUrl = $this->_url->getUrl('marketplace/account/login');

        if (!$this->_customerSession->authenticate($loginUrl)) {
            $this->_actionFlag->set('', self::FLAG_NO_DISPATCH, true);
        }

        return parent::dispatch($request);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $post = $this->getRequest()->getPostValue();
        if (!$post) {
            $this->_view->loadLayout();
            $this->_view->getPage()->getConfig()->getTitle()->set(__('Two-Factor Authentication'));
            $this->_view->renderLayout();
        } else {
            $secret = $this->secretFactory->create()->load($this->_customerSession->getCustomerId(), 'customer_id')->getSecret();
            if ($this->_authenticateQRCode($secret, $post['code'])) {
                $this->messageManager->addSuccessMessage(__('Two Factor Authentication successful'));
                $this->_customerSession->set2faSuccessful(true);
                return $this->_redirect('marketplace/account/dashboard');
                
            } else {
                $this->messageManager->addErrorMessage(__('Two Factor Authentication code incorrect'));
                $this->_customerSession->set2faSuccessful(false);
                return $this->_redirect('*/*/*');
            }
        }
    }

    /**
     * Authenticates QR code.
     *
     * @param $secret
     * @param $code
     * @param int $clockTolerance
     *
     * @return string
     */
    private function _authenticateQRCode($secret, $code, $clockTolerance = 2)
    {
        if (!$secret || !$code) {
            return false;
        }

        return $this->googleAuthenticator->verifyCode($secret, $code, $clockTolerance);
    }
}
