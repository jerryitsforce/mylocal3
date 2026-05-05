<?php
declare(strict_types=1);


namespace Branch8\WebkulMpBuyerSellerChat\Model\Actions;

use Branch8\WebkulMpBuyerSellerChat\Model\ChatProfileEntity;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\ResourceConnection;

class SellerNickNameResolver
{
    private ResourceConnection $resourceConnection;
    private CustomerRepositoryInterface $customerRepository;

    /**
     * @param ResourceConnection $resourceConnection
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        ResourceConnection          $resourceConnection,
        CustomerRepositoryInterface $customerRepository
    )
    {
        $this->customerRepository = $customerRepository;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param int $sellerId
     * @return string
     */
    public function execute(int $sellerId): string
    {
        try {
            $nickname = $this->getStoreName($sellerId);
            if ($nickname) {
                return $nickname;
            }
            $object = $this->customerRepository->getById($sellerId);
            $name = ChatProfileEntity::getProfileName(
                ChatProfileEntity::CUSTOMER, $object
            );
            $nickname = $name;
            if (($attribute = $object->getCustomAttribute('nickname'))
                && $attribute->getValue()
            ) {
                $nickname = $attribute->getValue();
            }
            return $nickname;
        } catch (\Exception $exception) {
            return __('My store')->render();
        }
    }

    /**
     * @param int $sellerId
     * @return string
     */
    private function getStoreName(int $sellerId): string
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select();
        $select->from('marketplace_userdata', ['shop_title'])->where('seller_id = ? ', $sellerId);
        $row = $connection->fetchRow($select);
        if ($row) {
            return $row['shop_title'];
        }
        return '';
    }
}
