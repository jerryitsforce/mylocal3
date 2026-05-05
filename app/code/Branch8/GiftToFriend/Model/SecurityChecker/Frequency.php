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
use Magento\Security\Model\ResourceModel\PasswordResetRequestEvent\CollectionFactory;

/**
 * Checker by frequency requests
 */
class Frequency implements \Magento\Security\Model\SecurityChecker\SecurityCheckerInterface
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $dateTime;

    /**
     * @var \Magento\Security\Model\ResourceModel\PasswordResetRequestEvent\CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var ConfigInterface
     */
    private $securityConfig;

    /**
     * @var RemoteAddress
     */
    private $remoteAddress;

    /**
     * @param \Branch8\GiftToFriend\Model\SecurityChecker\Config $securityConfig
     * @param CollectionFactory $collectionFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param RemoteAddress $remoteAddress
     */
    public function __construct(
        \Branch8\GiftToFriend\Model\SecurityChecker\Config $securityConfig,
        CollectionFactory $collectionFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        RemoteAddress $remoteAddress
    ) {
        $this->securityConfig = $securityConfig;
        $this->collectionFactory = $collectionFactory;
        $this->dateTime = $dateTime;
        $this->remoteAddress = $remoteAddress;
    }

    /**
     * @inheritdoc
     */
    public function check($securityEventType, $accountReference = null, $longIp = null)
    {
        $isEnabled = $this->securityConfig->getSmsProtectionType() != ResetMethod::OPTION_NONE;
        $limitTimeBetweenRequests = $this->securityConfig->getMinTimeBetweenSmsRequests();
        if ($isEnabled && $limitTimeBetweenRequests) {
            if (null === $longIp) {
                $longIp = $this->remoteAddress->getRemoteAddress();
            }
            $lastRecordCreationTimestamp = $this->loadLastRecordCreationTimestamp(
                $securityEventType,
                $accountReference,
                $longIp
            );
            if ($lastRecordCreationTimestamp && (
                    $limitTimeBetweenRequests >
                    ($this->dateTime->gmtTimestamp() - $lastRecordCreationTimestamp)
                )) {
                // throw new SecurityViolationException(
                //     ____('You have reached the limit of %1 verification code requests. Please try again after %2 hours. If you need assistance, please contact customer support.', , )
                // );
            }
        }
    }

    /**
     * Load last record creation timestamp
     *
     * @param int $securityEventType
     * @param string $accountReference
     * @param int $longIp
     * @return int
     */
    private function loadLastRecordCreationTimestamp($securityEventType, $accountReference, $longIp)
    {
        $collection = $this->collectionFactory->create($securityEventType, $accountReference, $longIp);
        /** @var \Magento\Security\Model\PasswordResetRequestEvent $record */
        $record = $collection->filterLastItem()->getFirstItem();

        return (int) strtotime($record->getCreatedAt() ?? '');
    }
}