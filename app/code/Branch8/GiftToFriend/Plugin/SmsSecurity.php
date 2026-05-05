<?php
namespace Branch8\GiftToFriend\Plugin;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Config\ScopeInterface;

class SmsSecurity
{
    
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var SecurityManager
     */
    protected $securityManager;

    /**
     * @var int
     */
    protected $smsRequestEvent;

    /**
     * @var ScopeInterface
     */
    private $scope;

    /**
     * AccountManagement constructor.
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @param SecurityManager $securityManager
     * @param int $passwordRequestEvent
     * @param ScopeInterface $scope
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Branch8\GiftToFriend\Model\SmsSecurityManager $securityManager,
        $smsRequestEvent = \Branch8\GiftToFriend\Model\SmsSecurityManager::SMS_SECURITY_GIFT_ORDER,
        ScopeInterface $scope = null
    ) {
        $this->request = $request;
        $this->securityManager = $securityManager;
        $this->smsRequestEvent = $smsRequestEvent;
        $this->scope = $scope ?: ObjectManager::getInstance()->get(ScopeInterface::class);
    }

    public function beforeExecute(
        $subject
    ) {
        $this->securityManager->performSecurityCheck(
            $this->smsRequestEvent,
            $subject->getRequest()->getParam('order_code')
        );

        return [];
    }
}