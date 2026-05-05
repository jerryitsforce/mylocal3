<?php

namespace Branch8\Sales\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Sales\Api\Data\OrderStatusHistoryInterface;
use Magento\Sales\Model\Order;
use Magento\Framework\App\ResourceConnection;

class Data extends AbstractHelper
{
    protected $rmaStatusOptions;

    protected $mpOrderCollectionFactory;

    protected $resourceConnection;

    public function __construct(
        Context                                                          $context,
        \Branch8\HotaiCore\Model\Config\Source\RmaStatus                 $rmaStatusOptions,
        \Webkul\Marketplace\Model\ResourceModel\Orders\CollectionFactory $mpOrderCollectionFactory,
        ResourceConnection                                               $resourceConnection
    )
    {
        parent::__construct($context);
        $this->rmaStatusOptions = $rmaStatusOptions;
        $this->mpOrderCollectionFactory = $mpOrderCollectionFactory;
        $this->resourceConnection = $resourceConnection;
    }

    public function getRMAStatus($order)
    {
        $rmaStatus = $order->getRmaStatus();
        if (!$rmaStatus) {
            return 'N/A';
        }
        $options = $this->rmaStatusOptions->getOptionArray();
        if (isset($options[$rmaStatus])) {
            return $options[$rmaStatus];
        }
        return 'N/A';
    }

    public function getEmailSubjectPrefix()
    {
        return $this->scopeConfig->getValue('sales_email/general/subject_prefix', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getSellerInfo($order)
    {
        $orderId = $order->getId();
        $mpOrderColl = $this->mpOrderCollectionFactory->create()
            ->addFieldToSelect('seller_id')
            ->addFieldToFilter('order_id', $orderId);
        $select = $mpOrderColl->getSelect();
        $select->joinLeft(['ct' => 'customer_grid_flat'], 'main_table.seller_id = ct.entity_id', ['name', 'customer_id' => 'ct.entity_id']);
        return $mpOrderColl->getFirstItem();
    }

    /**
     * @param Order $order
     * @param $username
     * @param $oldStatus
     * @param $newStatus
     * @return OrderStatusHistoryInterface
     */
    public function addWhoUpdateOrderStatus(Order $order, $username, $oldStatus, $newStatus)
    {
        $comment = __('%1 updated status from %2 to %3', $username, $oldStatus, $newStatus);
        $history = $order->addStatusHistoryComment($comment, $newStatus);//
        $history->setIsVisibleOnFront(false);
        $history->setIsCustomerNotified(false);
        return $history;
    }

    public function queryPostcodeForAddress($city, $region): string
    {
        $connection      = $this->resourceConnection->getConnection();
        $cityTableName   = $this->resourceConnection->getTableName('hotai_city_directory');
        $regionTableName = $this->resourceConnection->getTableName('directory_country_region');

        $select = $connection->select()->from($cityTableName);
        $select->joinLeft(
            ['directory_country_region' => $regionTableName],
            'directory_country_region.region_id = hotai_city_directory.region_id',
        );
        $select->where(
            "hotai_city_directory.city = ?",
            $city
        );
        $select->where(
            "directory_country_region.default_name = ?",
            $region
        );

        $result = $connection->fetchRow($select);

        return $result['zipcode'] ?? 'N/A';
    }
}
