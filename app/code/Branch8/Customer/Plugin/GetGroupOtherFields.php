<?php

namespace Branch8\Customer\Plugin;

use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\ResourceModel\GroupExcludedWebsiteRepository;

class GetGroupOtherFields{
    /**
     * @var \Magento\Customer\Api\Data\GroupExtensionInterfaceFactory
     */
    private $groupExtensionInterfaceFactory;
    /**
     * @var \Magento\Customer\Model\GroupRegistry
     */
    private $groupRegistry;

    /**
     * @param \Magento\Customer\Api\Data\GroupExtensionInterfaceFactory $groupExtensionInterfaceFactory
     * @param GroupExcludedWebsiteRepository $groupExcludedWebsiteRepository
     */
    public function __construct(
        \Magento\Customer\Api\Data\GroupExtensionInterfaceFactory $groupExtensionInterfaceFactory,
        \Magento\Customer\Model\GroupRegistry $groupRegistry
    ) {
        $this->groupExtensionInterfaceFactory = $groupExtensionInterfaceFactory;
        $this->groupRegistry = $groupRegistry;
    }

    /**
     * @param GroupRepositoryInterface $subject
     * @param GroupInterface $result
     * @param int $id
     * @return GroupInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function afterGetById(
        GroupRepositoryInterface $subject,
        GroupInterface $result,
        int $id
    ): GroupInterface {
        $groupModel = $this->groupRegistry->retrieve($id);
        if (!empty($groupModel)) {
            $customerGroupExtensionAttributes = $this->groupExtensionInterfaceFactory->create();
            $customerGroupExtensionAttributes->setOrganization($groupModel->getOrganization());
            $customerGroupExtensionAttributes->setFullname($groupModel->getFullname());
            $customerGroupExtensionAttributes->setNxtLevel($groupModel->getNxtLevel());
            $customerGroupExtensionAttributes->setPrevLevel($groupModel->getPrevLevel());
            $customerGroupExtensionAttributes->setPeriod($groupModel->getPeriod());
            $customerGroupExtensionAttributes->setConditions($groupModel->getConditions());
            $customerGroupExtensionAttributes->setLabel($groupModel->getLabel());
            $customerGroupExtensionAttributes->setIcon($groupModel->getIcon());
            $result->setExtensionAttributes($customerGroupExtensionAttributes);
        }

        return $result;
    }
}