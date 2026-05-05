<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceOrderExportRabbitMQ\Ui\DataProvider;

use Webkul\Marketplace\Helper\Data as HelperData;
use Webkul\Marketplace\Model\ResourceModel\Orders\Collection as OrderColl;
use Branch8\MarketPlaceOrderExportRabbitMQ\Model\ResourceModel\Grid\SellerCollectionFactory;

/**
 * Order History Data Provider
 */
class SellerDownloadProfileDataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * Collection for getting table name
     *
     * @var \Branch8\MarketPlaceOrderExportRabbitMQ\Model\ResourceModel\Grid\SellerCollection
     */
    protected $orderColl;

    /**
     * Saleslist Orders collection
     *
     * @var \Webkul\Marketplace\Model\ResourceModel\Orders\Collection
     */
    protected $collection;

    /**
     * @var HelperData
     */
    public $helperData;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param OrderColl $orderColl
     * @param \Branch8\MarketPlaceOrderExportRabbitMQ\Model\ResourceModel\Profile\CollectionFactory $collectionFactory
     * @param HelperData $helperData
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        string $name,
        string  $primaryFieldName,
        string $requestFieldName,
        OrderColl $orderColl,
        \Branch8\MarketPlaceOrderExportRabbitMQ\Model\ResourceModel\Profile\CollectionFactory $collectionFactory,
        HelperData $helperData,
        array $meta = [],
        array $data = []
    )
    {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $sellerId = $helperData->getCustomerId();
        $collectionData = $collectionFactory->create();
        $collectionData->getSelect()->where(
            'main_table.user_id = ?', $sellerId
        );
        $collectionData->getSelect()->where(
            'main_table.profile_type = ?', 'seller'
        );
        $totalFiles = new \Zend_Db_Expr(
            '(SELECT count(*) FROM branch8_order_export_profile_batches WHERE entity_id=parent_id)'
        );
        $totalCompletes = new \Zend_Db_Expr(
            '(SELECT count(*) FROM branch8_order_export_profile_batches WHERE entity_id=parent_id and batch_status="done")'
        );
        $collectionData->getSelect()->reset(\Zend_Db_Select::COLUMNS)->columns(
            [

                'entity_id' => 'entity_id',
                'status' => 'status',
                'receiver_email' => 'receiver_email',
                'receiver_name' => 'receiver_name',
                'email_sent' => 'email_sent',
                'total_files' => $totalFiles,
                'total_completed' => $totalCompletes,
                'publish_at' => 'publish_at',
                'created_at' => 'created_at'
            ]
        );
        $this->collection = $collectionData;
    }

    public function getAllIds()
    {
        return $this->getCollection()->getAllIds();
    }

    public function getCollection()
    {
        return $this->collection;
    }

    public function getData()
    {
        $collection = $this->getCollection();
        $data = $collection->toArray();
        return $data;
    }

}
