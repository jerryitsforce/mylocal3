<?php
declare(strict_types=1);

namespace Branch8\RmaAdminUi\Model\AdminRma;

use Branch8\Rma\Helper\Data;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order\ItemFactory;
use Branch8\Sales\Helper\Data as DataHelper;

/**
 *
 */
class PostDataToRmaObject
{
    /**
     * @var
     */
    private $orders = [];

    /** @var \Branch8\Rma\Helper\Data $helper */
    protected $helper;
    /**
     * @var \Webkul\MpRmaSystem\Helper\Data
     */
    protected $mpRmaHelper;
    /**
     * @var ItemFactory
     */
    private ItemFactory $itemFactory;
    /**
     * @var CustomerFactory
     */
    private CustomerFactory $customerFactory;
    /**
     * @var \Webkul\MpRmaSystem\Model\DetailsFactory
     */
    private \Webkul\MpRmaSystem\Model\DetailsFactory $detailFactory;
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;
    /**
     * @var \Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory
     */
    private \Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory $sellerCollectionFactory;

    /** @var DataHelper */
    protected $dataHelper;

    /**
     * @param ItemFactory $itemFactory
     * @param CustomerFactory $customerFactory
     * @param \Webkul\MpRmaSystem\Helper\Data $mpRmaHelper
     * @param Data $helper
     * @param \Webkul\MpRmaSystem\Model\DetailsFactory $details
     * @param ResourceConnection $connection
     * @param \Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory $sellerCollectionFactory
     */
    public function __construct(
        ItemFactory                                                      $itemFactory,
        CustomerFactory                                                  $customerFactory,
        \Webkul\MpRmaSystem\Helper\Data                                  $mpRmaHelper,
        \Branch8\Rma\Helper\Data                                         $helper,
        \Webkul\MpRmaSystem\Model\DetailsFactory                         $details,
        ResourceConnection                                               $connection,
        \Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory $sellerCollectionFactory,
        DataHelper                                                       $dataHelper
    )
    {
        $this->sellerCollectionFactory = $sellerCollectionFactory;
        $this->helper = $helper;
        $this->customerFactory = $customerFactory;
        $this->itemFactory = $itemFactory;
        $this->mpRmaHelper = $mpRmaHelper;
        $this->detailFactory = $details;
        $this->resourceConnection = $connection;
        $this->dataHelper = $dataHelper;
    }

    /**
     * @return array|void
     */
    public function rmaReasonIdToLabel()
    {
        $select = $this->resourceConnection->getConnection()->select();
        $select->from('marketplace_rma_reasons', ['id', 'reason']);
        $data = $this->resourceConnection->getConnection()->fetchPairs($select);
        if (($data)) {
            return $data;
        }
        return [];
    }

    /**
     * @param $postData
     * @return array
     */
    private function getRmaItems($postData)
    {
        return array_filter($postData['list_rma_items'], function ($item) {
            if (filter_var($item['is_checked'], FILTER_VALIDATE_BOOL)) {
                return $item;
            };
        });
    }

    /**
     * @param $postData
     * @return array
     * @throws LocalizedException
     */
    private function prepareBasicInformation($postData)
    {
        $reasonIdToLabel = $this->rmaReasonIdToLabel();
        $time = date('Y-m-d H:i:s');
        $orderId = $postData['order_id'];
        $order = $this->mpRmaHelper->getOrder($orderId);
        $customerId = $order->getCustomerId() ? $order->getCustomerId() : null;
        if ($customerId) {
            $customer = $this->customerFactory->create()->load($customerId);
            $email = $customer->getBuyerEmail();
            $customerName = $customer->getName();
        } else {
            $email = $order->getCustomerEmail();
            $customerName = $order->getCustomerName();
        }
        $saveData['customer_tax_id_number'] = $order->getEcpayInvoiceCustomerIdentifier();
        $saveData['rma_reason_id'] = $postData['reasons'];
        $saveData['rma_reason'] = $reasonIdToLabel[$saveData['rma_reason_id']] ?? '';
        $saveData['customer_id'] = $customerId;
        $saveData['customer_name'] = $customerName;
        $saveData['customer_email'] = $email;
        $saveData['seller_status'] = Data::SELLER_STATUS_PENDING;
        $saveData['status'] = $this->helper->iniRmaStatus($postData['resolution_type']);
        $saveData['created_date'] = $time;
        $saveData['updated_date'] = $time;
        $saveData['order_ref'] = '#' . $order->getIncrementId();
        $saveData['rma_receiver'] = $postData['rma_receiver'];
        $saveData['order_id'] = $postData['order_id'];

        if (empty($postData['rma_zipcode']) && !empty($postData['rma_city']) && !empty($postData['rma_region'])) {
            $postData['rma_zipcode'] = $this->dataHelper->queryPostcodeForAddress($postData['rma_city'], $postData['rma_region']);
        }

        /**
         * format Zip Code, City, District, Address.
         */
        $address = implode(',', array_filter($postData['rma_address'], function ($item) {
            return !empty($item);
        }));
        $saveData['rma_address'] = implode(',',
            array_filter([
                $this->helper->cleanSpecialChar((string)$postData['rma_zipcode']),
                $postData['rma_region'],
                $postData['rma_city'],
                $address
            ])
        );
        $saveData['resolution_type'] = $postData['resolution_type'];
        $saveData['rma_phone'] = $postData['rma_phone'];
        if (!is_null($postData['rma_delivery_time'])) {
            $saveData['rma_delivery_time'] = $postData['rma_delivery_time'];
        }
        $saveData['rma_application_details'] = $this->helper->jsonEncodeData($postData);
        return $saveData;
    }

    /**
     * @param $postData
     * @return array
     */
    private function prepareRmaItems($postData)
    {
        $saveData = [];
        $requestItemInformation = [];
        $rmaItems = $this->getRmaItems($postData);
        $productIds = [];
        foreach ($rmaItems as $item) {
            $orderItem = (object)$this->getOrderItemDetails($item['item_id']);
            $productId = $orderItem->getProductId();
            $requestItemInformation[$item['item_id']][] = [
                'item_id' => $orderItem->getItemId(),
                'product_id' => $productId,
                'qty' => $item['original_qty'],
                'price' => $orderItem->getPrice(),
                'reason_id' => $postData['reasons']
            ];
            if ($productId) {
                $productIds[] = $productId;
            }
        }
        $saveData['product_id'] = implode(",", array_unique($productIds));
        $saveData['request_item_information'] = $requestItemInformation;
        return $saveData;
    }

    /**
     * Since we split order follow by product seller , so probably one order only for 1 seller
     * @return array
     */
    private function prepareSellerDetails($postData)
    {
        $order = $this->getOrder($postData['order_id']);
        $collection = $this->sellerCollectionFactory->create();
        $collection->getSelect()->join(
            'marketplace_orders',
            'main_table.seller_id = marketplace_orders.seller_id',
            ['order_id']
        );
        $select = $collection->getSelect();
        $select->joinLeft('customer_entity',
            'main_table.seller_id = customer_entity.entity_id',
            [
                'seller_name' => new \Zend_Db_Expr("CONCAT(firstname,' ',lastname)"),
            ]
        );
        $select->where('marketplace_orders.order_id = ? ', $order->getId());
        /**
         *
         */
        $count = $collection->getSize();

        // if ($count > 1) {
        //     throw new LocalizedException(__('Multiple seller for one order,please contact administrator.'));
        // }

        /**
         * @var $seller \Webkul\Marketplace\Model\Seller
         */
        if ($count) {
            $seller = $collection->getFirstItem();
            $saveData['product_seller'] = $seller->getSellerName() ?? 'Seller';
            $saveData['seller_id'] = (int)$seller->getSellerId();
        } else {
            $saveData['product_seller'] = 'Admin';
        }
        return $saveData;
    }

    /**
     * @param $orderId
     * @return mixed
     * @throws NoSuchEntityException
     */

    private function getOrder($orderId)
    {
        if (isset($this->orders[$orderId])) {
            return $this->orders[$orderId];
        }
        $order = $this->mpRmaHelper->getOrder($orderId);
        if (!$order->getId()) {
            throw new NoSuchEntityException(__('No order found'));
        }
        $this->orders[$orderId] = $order;
        return $this->orders[$orderId];
    }

    /**
     * @param $postData
     * @return \Webkul\MpRmaSystem\Model\Details
     * @throws LocalizedException
     */
    public function build($postData)
    {
        /**
         * @var $order \Magento\Sales\Model\Order
         */
        $basicData = $this->prepareBasicInformation($postData);
        $rmaItems = $this->prepareRmaItems($postData);
        $sellerDetails = $this->prepareSellerDetails($postData);
        return $this->detailFactory->create()->setData(array_merge($basicData, $rmaItems, $sellerDetails));
    }

    /**
     * @param $itemId
     * @return \Magento\Sales\Model\Order\Item
     */
    private function getOrderItemDetails($itemId)
    {
        return $this->itemFactory->create()->load($itemId);
    }
}
