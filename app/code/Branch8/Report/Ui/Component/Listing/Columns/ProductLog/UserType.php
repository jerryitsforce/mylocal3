<?php

declare(strict_types=1);

namespace Branch8\Report\Ui\Component\Listing\Columns\ProductLog;

use Branch8\Report\Api\Data\ProductChangeLogInterface;
use Branch8\Report\Model\Source\UserType as UserTypeSource;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\User\Model\UserFactory;

class UserType extends Column
{
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
     * @param ContextInterface $context
     * @param UserFactory $userFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param UiComponentFactory $uiComponentFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface            $context,
        UserFactory                 $userFactory,
        CustomerRepositoryInterface $customerRepository,
        UiComponentFactory          $uiComponentFactory,
        array                       $components = [],
        array                       $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->userFactory = $userFactory;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource): array
    {
        $fieldName = $this->getData('name');
        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item[$fieldName])) {
                continue;
            }

            $rawValue = (int)$item[$fieldName];
            $item[$fieldName] = UserTypeSource::toArray()[$rawValue] ?? __('Unknown User');
            if ($rawValue === UserTypeSource::TYPE_ADMIN) {
                try {
                    $userId = $item[ProductChangeLogInterface::USER_ID] ?? 0;
                    $user = $this->userFactory->create()->load($userId);
                    if ($user->getId()) {
                        $item[ProductChangeLogInterface::USER_ID] = $user->getUsername();
                    } else {
                        $item[ProductChangeLogInterface::USER_ID] = __('Unknown Admin');
                    }
                } catch (\Exception $e) {
                    $item[ProductChangeLogInterface::USER_ID] = __('Unknown Admin');
                }
            } elseif ($rawValue === UserTypeSource::TYPE_SELLER) {
                $sellerId = $item[ProductChangeLogInterface::USER_ID] ?? 0;
                try {
                    $seller = $this->customerRepository->getById($sellerId);
                    $item[ProductChangeLogInterface::USER_ID] = $seller->getEmail();
                } catch (\Exception $e) {
                    $item[ProductChangeLogInterface::USER_ID] = __('Unknown Seller');
                }
            } else {
                $item[ProductChangeLogInterface::USER_ID] = '';
            }
        }

        return $dataSource;
    }
}
