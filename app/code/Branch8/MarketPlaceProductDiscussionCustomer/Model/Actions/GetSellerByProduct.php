<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       08/03/2026
 */

namespace Branch8\MarketPlaceProductDiscussionCustomer\Model\Actions;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\ResourceConnection;

class GetSellerByProduct
{
    private ResourceConnection $resourceConnection;
    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

    /**
     * @param ResourceConnection $resourceConnection
     * @param CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        CustomerRepositoryInterface $customerRepository
    )
    {
        $this->resourceConnection = $resourceConnection;
        $this->customerRepository = $customerRepository;
    }

    /***
     * @param int $productId
     * @return \Magento\Customer\Api\Data\CustomerInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get(int $productId)
    {
        $select = $this->resourceConnection->getConnection()->select();
        $select->from('marketplace_product', ['seller_id'])->where('mageproduct_id = ?', $productId);
        return $this->customerRepository->getById((int)$this->resourceConnection->getConnection()->fetchOne($select));
    }
}
