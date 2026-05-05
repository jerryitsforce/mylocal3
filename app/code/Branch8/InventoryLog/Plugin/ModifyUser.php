<?php

declare(strict_types=1);

namespace Branch8\InventoryLog\Plugin;

use Branch8\Report\Helper\Data as ReportHelper;
use Branch8\Report\Model\Source\UserType;
use Elgentos\InventoryLog\Helper\Data as InventoryLogHelper;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\User\Model\UserFactory;

class ModifyUser
{
    /**
     * @var ReportHelper
     */
    private ReportHelper $reportHelper;

    /**
     * @var UserFactory
     */
    private UserFactory $userFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

    /**
     * Constructor.
     *
     * @param ReportHelper $reportHelper
     * @param UserFactory $userFactory
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        ReportHelper                $reportHelper,
        UserFactory                 $userFactory,
        CustomerRepositoryInterface $customerRepository)
    {
        $this->reportHelper = $reportHelper;
        $this->userFactory = $userFactory;
        $this->customerRepository = $customerRepository;
    }

    /**
     * Check admin isLoggedIn.
     *
     * @param InventoryLogHelper $subject
     * @param int $result
     *
     * @return int
     */
    public function afterIsAdminLoggedIn(InventoryLogHelper $subject, $result): int
    {
        $user = $this->reportHelper->getUpdatedByUser();
        $type = key($user);
        return (int)($type === UserType::TYPE_ADMIN);
    }

    /**
     * Returns logged user ID.
     *
     * @param InventoryLogHelper $subject
     * @param int $result
     *
     * @return int
     */
    public function afterGetUserId(InventoryLogHelper $subject, $result): int
    {
        $user = $this->reportHelper->getUpdatedByUser();
        return (int)reset($user);
    }

    /**
     * Returns logged username.
     *
     * @param InventoryLogHelper $subject
     * @param mixed $result
     *
     * @return mixed
     */
    public function afterGetUsername(InventoryLogHelper $subject, $result): mixed
    {
        $user = $this->reportHelper->getUpdatedByUser();
        $userId = reset($user);

        if (empty($userId)) {
            return $result;
        }

        $type = key($user);
        if ($type === UserType::TYPE_ADMIN) {
            try {
                $user = $this->userFactory->create()->load($userId);
                if ($user->getId()) {
                    return $user->getUsername();
                } else {
                    return $result;
                }
            } catch (\Exception $e) {
                return $result;
            }
        } elseif ($type === UserType::TYPE_SELLER) {
            try {
                $seller = $this->customerRepository->getById($userId);
                return $seller->getFirstname() . ' ' . $seller->getLastname();
            } catch (\Exception $e) {
                return $result;
            }
        }
        return $result;
    }
}
