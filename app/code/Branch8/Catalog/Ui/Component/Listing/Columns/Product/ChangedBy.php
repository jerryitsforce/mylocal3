<?php

declare(strict_types=1);

namespace Branch8\Catalog\Ui\Component\Listing\Columns\Product;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\User\Model\UserFactory;

class ChangedBy extends Column
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

            $rawValue = $item[$fieldName];
            if ($rawValue === 'admin') {
                $item[$fieldName] = __('Admin');
                try {
                    $userId = $item[$fieldName . '_id'] ?? 0;
                    $user = $this->userFactory->create()->load($userId);
                    if ($user->getId()) {
                        $item[$fieldName . '_id'] = $user->getUsername();
                    } else {
                        $item[$fieldName . '_id'] = __('Unknown Admin');
                    }
                } catch (\Exception $e) {
                    $item[$fieldName . '_id'] = __('Unknown Admin');
                }
            } elseif ($rawValue === 'seller') {
                $item[$fieldName] = __('Seller');

                $sellerId = $item[$fieldName . '_id'] ?? 0;
                try {
                    $seller = $this->customerRepository->getById($sellerId);
                    $item[$fieldName . '_id'] = $seller->getEmail();
                } catch (\Exception $e) {
                    $item[$fieldName . '_id'] = __('Unknown Seller');
                }
            } else {
                $item[$fieldName] = __('System');
            }
        }

        return $dataSource;
    }
}
