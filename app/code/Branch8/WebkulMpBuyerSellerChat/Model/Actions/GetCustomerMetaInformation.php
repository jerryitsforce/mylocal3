<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChat\Model\Actions;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\UrlInterface;
use Webkul\Marketplace\Model\ResourceModel\Saleslist\CollectionFactory as MpSaleslistCollectionFactory;

class GetCustomerMetaInformation
{
    /**
     * @var array
     */
    private $metaInformationCached = [];
    /**
     * @var MpSaleslistCollectionFactory
     */
    private $mpSaleslistCollectionFactory;
    private ResourceConnection $resource;
    private CustomerRepository $customerRepository;
    private UrlInterface $url;
    private \Magento\Framework\Pricing\Helper\Data $currencyHelper;

    /**
     * @param ResourceConnection $resourceConnection
     * @param CustomerRepository $customerRepository
     * @param UrlInterface $url
     * @param MpSaleslistCollectionFactory $mpSalesListCollectionFactory
     * @param \Magento\Framework\Pricing\Helper\Data $currencyHelper
     */
    public function __construct(
        ResourceConnection                     $resourceConnection,
        CustomerRepository                     $customerRepository,
        UrlInterface                           $url,
        MpSaleslistCollectionFactory           $mpSalesListCollectionFactory,
        \Magento\Framework\Pricing\Helper\Data $currencyHelper
    )
    {
        $this->customerRepository = $customerRepository;
        $this->resource = $resourceConnection;
        $this->mpSaleslistCollectionFactory = $mpSalesListCollectionFactory;
        $this->url = $url;
        $this->currencyHelper = $currencyHelper;
    }

    /**
     * @param int $sellerId
     * @param int $customerId
     * @return array|mixed
     */
    public function execute(int $sellerId, int $customerId)
    {
        if (isset($this->metaInformationCached[$customerId])) {
            return $this->metaInformationCached[$customerId];
        }
        $this->metaInformationCached[$customerId] = $this->getProfileData(
            $sellerId,
            $customerId
        );
        return $this->metaInformationCached[$customerId];
    }

    /**
     * This function might be slow
     * @param int $sellerId
     * @param int $customerId
     * @return array
     */
    private function getProfileData(int $sellerId, int $customerId)
    {
        /**
         * @TODO  create index table for CustomerMetaInformation
         */
        $profileData = [
            'address' => "-",
            'seller_amount' => "-",
            'phone_number' => "-",
            'gender' => "-",
            'order_count' => "-",
            'visible' => false
        ];
        $customerData = $this->getCustomerFromIndex($customerId);
        if (empty($customerData)) {
            return $profileData;
        }
        $mpSalesList = $this->mpSaleslistCollectionFactory->create()
            ->addFieldToFilter(
                'seller_id', ['eq' => $sellerId]
            )->addFieldToFilter(
                'magebuyer_id', ['eq' => $customerId]
            );
        $profileData = [
            'name' => $customerData['name'],
            'orderHistoryLink' => $this->url->getUrl(
                'marketplace/order/history',
                ['customer_id' => $customerId]
            ),
            'email' => $customerData['email'],
            'address' => $customerData['shipping_full'],
            'phone_number' => $customerData['billing_telephone'],
            'gender' => (int)$customerData['gender'],
            'visible' => true
        ];
        if ($totalOrders = $mpSalesList->getSize()) {
            $profileData['seller_amount'] = $this->currencyHelper->currency(
                (float)$mpSalesList->getTotalSellerAmount()[0]['actual_seller_amount']
            );
            $profileData['order_count'] = $totalOrders;
        }
        return $profileData;
    }

    /**
     * @param $customerId
     * @return mixed
     */
    private function getCustomerFromIndex($customerId)
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from('customer_grid_flat')->where('entity_id = ?', $customerId);
        return $connection->fetchRow($select);
    }
}
