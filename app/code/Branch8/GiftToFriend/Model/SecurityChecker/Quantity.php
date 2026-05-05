<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\GiftToFriend\Model\SecurityChecker;

use Magento\Framework\Exception\SecurityViolationException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Security\Model\Config\Source\ResetMethod;
use Magento\Security\Model\ConfigInterface;
use Branch8\GiftToFriend\Model\ResourceModel\SmsRequestEvent\CollectionFactory;

/**
 * Check by requests number per fixed period of time
 */
class Quantity implements \Magento\Security\Model\SecurityChecker\SecurityCheckerInterface
{
    /**
     * @var \Branch8\GiftToFriend\Model\ResourceModel\SmsRequestEvent\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var ConfigInterface
     */
    protected $securityConfig;

    /**
     * @var RemoteAddress
     */
    private $remoteAddress;

    /**
     * @param \Branch8\GiftToFriend\Model\SecurityChecker\Config $securityConfig
     * @param CollectionFactory $collectionFactory
     * @param RemoteAddress $remoteAddress
     */
    public function __construct(
        \Branch8\GiftToFriend\Model\SecurityChecker\Config $securityConfig,
        CollectionFactory $collectionFactory,
        RemoteAddress $remoteAddress
    ) {
        $this->securityConfig = $securityConfig;
        $this->collectionFactory = $collectionFactory;
        $this->remoteAddress = $remoteAddress;
    }

    /**
     * @inheritdoc
     */
    public function check($securityEventType, $accountReference = null, $longIp = null)
    {
        $isEnabled = $this->securityConfig->getSmsProtectionType() != ResetMethod::OPTION_NONE;
        $allowedAttemptsNumber = $this->securityConfig->getMaxNumberSmsRequests();
        if ($isEnabled && $allowedAttemptsNumber) {
            $collection = $this->prepareCollection($securityEventType, $accountReference, $longIp);
            if ($collection->count() >= $allowedAttemptsNumber) {
                $timePreiodConfig = $this->securityConfig->getLimitationTimePeriod();

                if($timePreiodConfig < 60){
                    $timePreiod = __('%1 seconds', $timePreiodConfig);
                }else if($timePreiodConfig >= 60 && $timePreiodConfig < 60*60){
                    $timePreiod = __('%1 minutes', ceil($timePreiodConfig/60));
                }else{
                    $timePreiod = __('%1 hours', ceil($timePreiodConfig/(60*60)));
                }
                throw new SecurityViolationException(
                    __(
                        'You have reached the limit of %1 verification code requests. Please try again after %2. If you need assistance, please contact customer support.',
                        $allowedAttemptsNumber, $timePreiod
                    )
                );
            }
        }
    }

    /**
     * Prepare collection
     *
     * @param int $securityEventType
     * @param string $accountReference
     * @param int $longIp
     * @return \Magento\Security\Model\ResourceModel\PasswordResetRequestEvent\Collection
     */
    protected function prepareCollection($securityEventType, $accountReference, $longIp)
    {
        if (null === $longIp) {
            $longIp = $this->remoteAddress->getRemoteAddress();
        }
        $collection = $this->collectionFactory->create($securityEventType, $accountReference, $longIp);
        $periodToCheck = $this->securityConfig->getLimitationTimePeriod();
        $collection->filterByLifetime($periodToCheck);

        return $collection;
    }
}