<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\User\Model\ResourceModel\User\Collection as UserCollection;
use Magento\User\Model\ResourceModel\User\CollectionFactory as UserCollectionFactory;
use Magento\User\Model\User;

class Reviewer implements OptionSourceInterface
{
    /**
     * @var UserCollectionFactory
     */
    private UserCollectionFactory $userCollectionFactory;

    /**
     * AdminUser constructor.
     *
     * @param UserCollectionFactory $userCollectionFactory
     */
    public function __construct(UserCollectionFactory $userCollectionFactory)
    {
        $this->userCollectionFactory = $userCollectionFactory;
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        $options = [['value' => '', 'label' => '']];

        $userCollection = $this->getUserCollection();
        /** @var User $user */
        foreach ($userCollection as $user) {
            $name = null;
            if (!empty($user->getFirstName())) {
                $name = $user->getFirstName();
                if (!empty($user->getLastName())) {
                    $name .= ' ' . $user->getLastName();
                }
            }
            $options[] = [
                'value' => $user->getId(),
                'label' => $name
            ];
        }

        return $options;
    }

    /**
     * Retrieve user collection.
     *
     * @return UserCollection
     */
    private function getUserCollection(): UserCollection
    {
        $userCollection = $this->userCollectionFactory->create()
            ->addFieldToSelect(['user_id', 'firstname', 'lastname', 'email', 'username']);

        $userCollection->getSelect()
            ->joinInner(
                ['sub_table' => $userCollection->getTable('marketplace_product_version')],
                'sub_table.reviewer_id = main_table.user_id',
                []
            )
            ->group('user_id');

        return $userCollection;
    }
}
