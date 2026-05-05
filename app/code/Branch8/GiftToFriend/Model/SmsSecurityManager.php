<?php
namespace Branch8\GiftToFriend\Model;

use Magento\Framework\Exception\SecurityViolationException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Security\Model\SecurityChecker\SecurityCheckerInterface;

class SmsSecurityManager extends \Magento\Security\Model\SecurityManager
{
    const SMS_SECURITY_GIFT_ORDER = 2;

    protected $smsSecurityCheckers;

    /**
     * @var RemoteAddress
     */
    private $remoteAddress;

    /**
     * SecurityManager constructor.
     *
     * @param ConfigInterface $securityConfig
     * @param \Magento\Security\Model\PasswordResetRequestEventFactory $passwordResetRequestEventFactory
     * @param ResourceModel\PasswordResetRequestEvent\CollectionFactory $passwordResetRequestEventCollectionFactory
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param RemoteAddress $remoteAddress
     * @param array $securityCheckers
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function __construct(
        \Magento\Security\Model\ConfigInterface $securityConfig,
        \Magento\Security\Model\PasswordResetRequestEventFactory $passwordResetRequestEventFactory,
        \Magento\Security\Model\ResourceModel\PasswordResetRequestEvent\CollectionFactory $passwordResetRequestEventCollectionFactory,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        RemoteAddress $remoteAddress,
        $smsSecurityCheckers = []
    ) {
        parent::__construct($securityConfig, $passwordResetRequestEventFactory, $passwordResetRequestEventCollectionFactory, 
            $eventManager, $dateTime, $remoteAddress, $smsSecurityCheckers);
        $this->smsSecurityCheckers = $smsSecurityCheckers;
        $this->remoteAddress = $remoteAddress;
        foreach ($this->smsSecurityCheckers as $checker) {
            if (!($checker instanceof SecurityCheckerInterface)) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Incorrect Security Checker class. It has to implement SecurityCheckerInterface')
                );
            }
        }
    }

    /**
     * Perform security check
     *
     * @param int $requestType
     * @param string|null $accountReference
     * @param int|null $longIp
     * @return $this
     * @throws SecurityViolationException
     * @since 100.1.0
     */
    public function performSecurityCheck($requestType, $accountReference = null, $longIp = null)
    {
        if (null === $longIp) {
            $longIp = $this->remoteAddress->getRemoteAddress();
        }
        foreach ($this->smsSecurityCheckers as $checker) {
            $checker->check($requestType, $accountReference, $longIp);
        }

        $this->createNewPasswordResetRequestEventRecord($requestType, $accountReference, $longIp);

        return $this;
    }
}