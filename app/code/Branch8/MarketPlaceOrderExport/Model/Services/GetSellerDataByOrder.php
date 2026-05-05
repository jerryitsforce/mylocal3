<?php

namespace Branch8\MarketPlaceOrderExport\Model\Services;

use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;

class GetSellerDataByOrder
{
    private $cachedSellerId = [];
    private $sellerInformation = [];
    public ResourceConnection $resourceConnection;
    private CustomerRepository $customerRepository;

    /**
     * @param ResourceConnection $resourceConnection
     * @param CustomerRepository $customerRepository
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        CustomerRepository $customerRepository
    )
    {
        $this->customerRepository = $customerRepository;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param $orderId
     * @return mixed|string[]
     */
    public function get($orderId)
    {
        if (isset($this->cachedSellerId[$orderId])) {
            $sellerId = $this->cachedSellerId[$orderId];
        } else {
            $sellerId = $this->findSellerIdByOrderId($orderId);
            $this->cachedSellerId[$orderId] = (int)$sellerId;
        }
        if (isset($this->sellerInformation[$sellerId])) {
            return $this->sellerInformation[$sellerId];
        }
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                'marketplace_userdata',
                ['seller_code', 'special_dealer_code', 'special_dealer_name']
            )->where('seller_id = ?', $sellerId);
        $row = $connection->fetchRow($select);
        if ($row) {
            $customer = $this->customerRepository->getById($sellerId);
            $row['seller_name'] = $customer ? $customer->getFirstname() . ' ' . $customer->getLastname() : '';
            $this->sellerInformation[$sellerId] = $row;
        } else {

            $this->sellerInformation[$sellerId] = [
                'special_dealer_code' => '',
                'special_dealer_name' => '',
                'seller_code' => '',
                'seller_name' => ''
            ];
        }
        return $this->sellerInformation[$sellerId];
    }

    /**
     * @param int $orderId
     * @return string
     */
    private function findSellerIdByOrderId(int $orderId)
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from('marketplace_orders', ['seller_id'])
            ->where('order_id = ?', $orderId);
        $sellerId = $connection->fetchOne($select);
        return $sellerId;
    }

    /**
     * @param $sellerId
     * @return \Magento\Customer\Api\Data\CustomerInterface|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getSellerInformation($sellerId)
    {
        try {
            return $this->customerRepository->getById($sellerId);
        } catch (NoSuchEntityException $exception) {
            return '';
        }
    }
}
