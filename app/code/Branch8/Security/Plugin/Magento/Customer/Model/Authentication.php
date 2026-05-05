<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Security\Plugin\Magento\Customer\Model;

use Mageplaza\Security\Helper\Data;
use Magento\Framework\HTTP\PhpEnvironment\Request;
use Mageplaza\Security\Model\LoginLogFactory;
use Mageplaza\Security\Model\Config\Source\LoginLog\Status;
use Magento\Customer\Model\Session;
use Magento\Framework\UrlInterface;
use Magento\Framework\HTTP\Header;

class Authentication
{
    /**
     * @var Data
     */
    protected $_helperData;

    /**
     * @var Request
     */
    protected $_request;

    /**
     * @var LoginLogFactory
     */
    protected $_loginLogFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * @var Header
     */
    protected $_header;


    /**
     * LoginSuccess constructor.
     *
     * @param Request $request
     * @param Session $session
     * @param LoginLogFactory $loginLogFactory
     * @param Data $helperData
     */
    public function __construct(
        Request $request,
        Session $session,
        LoginLogFactory $loginLogFactory,
        Data $helperData,
        UrlInterface $urlBuilder,
        Header $header
    ) {
        $this->_request         = $request;
        $this->_customerSession  = $session;
        $this->_loginLogFactory = $loginLogFactory;
        $this->_helperData      = $helperData;
        $this->_urlBuilder = $urlBuilder;
        $this->_header = $header;
    }

    public function afterProcessAuthenticationFailure(
        \Magento\Customer\Model\Authentication $subject,
        $result,
        $customerId
    ) {
        if ($this->_helperData->isEnabled()) {
            if ($this->_request->getPost('vendor_login')) {
                $login = $this->_request->getPost('login');
                $browser_agent = $this->_helperData->getBrowser($this->_header->getHttpUserAgent())
                    . '--' . $this->_header->getHttpUserAgent();
                $url = $this->_urlBuilder->getUrl("marketplace/account/login");
                $referer = $this->_urlBuilder->getUrl("marketplace/account/dashboard");
                $loginLog = [
                    'time'          => time(),
                    'user_name'     => $login['username'],
                    'ip'            => $this->_request->getClientIp(),
                    'browser_agent' => $browser_agent,
                    'url'           => $url,
                    'referer'       => $referer,
                    'status'        => Status::STATUS_FAIL
                ];
                $this->_loginLogFactory->create()->addData($loginLog)->save();
            }
        }
        return $result;
    }
}