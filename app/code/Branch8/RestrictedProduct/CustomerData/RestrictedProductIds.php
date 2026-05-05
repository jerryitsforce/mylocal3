<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\RestrictedProduct\CustomerData;

use Amasty\Groupcat\Model\ProductRuleProvider;
use Magento\Customer\Api\Data\GroupInterface;
class RestrictedProductIds implements \Magento\Customer\CustomerData\SectionSourceInterface
{
    protected $customerSession;

    protected $logger;

    /**
     * @var ProductRuleProvider
     */
    private $ruleProvider;

    /**
     * Constructor
     *
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Customer\Model\Session $customerSession,
        ProductRuleProvider $ruleProvider
    )
    {
        $this->customerSession = $customerSession;
        $this->logger = $logger;
        $this->ruleProvider = $ruleProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getSectionData()
    {
        $groupId = GroupInterface::NOT_LOGGED_IN_ID;
        if($this->customerSession->getCustomerId()){
            $groupId = $this->customerSession->getCustomerGroupId();
        }
        return [
            'ids' => $this->ruleProvider->getRestrictedProductIds(),
            'customer_group_id' => $groupId
        ];
    }
}

