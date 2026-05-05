<?php

namespace Branch8\Rma\Model\Actions;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Psr\Log\LoggerInterface;

class PrepareRmaDataForForm
{
    /**
     * Log option value for this action class.
     */
    private const LOG_OPTION = 'PrepareRmaDataForForm';

    private $sellers = [];
    /**
     * @var OrderRepository
     */
    private OrderRepository $orderRepository;
    /**
     * @var  \Branch8\Rma\Helper\Data
     */
    private $mpRmaHelper;
    /**
     * @var \Magento\Catalog\Helper\Image
     */
    private $imageHelper;

    /**  @var \Branch8\HotaiCore\Helper\VirtualProduct $virtualProduct */
    private $virtualProduct;
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory
     */
    private \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItemCollectionFactory;

    /**
     * @param \Branch8\Rma\Helper\Data $mpRmaHelper
     * @param OrderRepository $orderRepository
     * @param \Magento\Catalog\Helper\Image $imageHelper
     * @param \Branch8\HotaiCore\Helper\VirtualProduct $virtualProduct
     * @param LoggerInterface $logger
     * @param \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItemCollectionFactory
     */
    public function __construct(
        \Branch8\Rma\Helper\Data                                        $mpRmaHelper,
        OrderRepository                                                 $orderRepository,
        \Magento\Catalog\Helper\Image                                   $imageHelper,
        \Branch8\HotaiCore\Helper\VirtualProduct                        $virtualProduct,
        LoggerInterface                                                 $logger,
        \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItemCollectionFactory
    )
    {
        $this->imageHelper = $imageHelper;
        $this->mpRmaHelper = $mpRmaHelper;
        $this->orderRepository = $orderRepository;
        $this->virtualProduct = $virtualProduct;
        $this->logger = $logger;
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;
    }

    /**
     * @param array $orderItems
     * @return array
     */
    private function getSellerDetail(array $orderItems)
    {
        /**
         * @var $item \Magento\Sales\Model\Order\Item
         */
        foreach ($orderItems as $item) {
            try {
                if ($item->getProduct()) {
                    $details = $this->mpRmaHelper->getSellerDetailsByProductId($item->getProduct()->getId());
                    $this->sellers[$details['seller_id']] = $details['seller_name'];
                }
            } catch (\Exception $exception) {
                \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Branch8\Rma\Helper\Log::class)
                    ->exception($exception, self::LOG_OPTION, __METHOD__, ['item_id' => $item->getId()]);
            }
        }
        return $this->sellers;
    }

    /**
     * @param Order $order
     * @param array $orderItems
     * @return mixed
     */
    private function getOrderDetail(Order $order, array $orderItems)
    {
        /**
         * @var $item \Magento\Sales\Model\Order\Item
         */
        $orderDetails = [];
        foreach ($orderItems as $item) {
            $product = $item->getProduct();
            if ($product) {
                $details = $this->mpRmaHelper->getSellerDetailsByProductId($product->getId());
                $orderDetails[$details['seller_id']][] = $item->getId();
            }
        }
        if (!$orderDetails) {
            return [];
        }
        $orderDetails = $this->getStatusDetails($orderDetails);
        return $this->setOrderDetailsAddressInfo($order, $orderDetails);
    }

    /**
     * @param int $orderId
     * @param int|null $itemId
     * @return array
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(int $orderId, int $itemId = null)
    {
        /**
         * @var $order \Magento\Sales\Model\Order
         */
        $order = $this->orderRepository->get($orderId);
        $storeId = (int)$order->getStoreId();
        if ($itemId) {
            $orderItems[] = $this->getOrderItem($order, $itemId);
        } else {
            // get remain items
            $collection = $this->orderItemCollectionFactory
                ->create();
            $collection->getSelect()
                ->joinLeft(['marketplace_rma_items' => 'marketplace_rma_items'],
                    'main_table.item_id=marketplace_rma_items.item_id',
                    []
                )->where(
                    'order_id = ?', $order->getId()
                )->where(new \Zend_Db_Expr('parent_item_id IS NULL'))
                ->where(new \Zend_Db_Expr('marketplace_rma_items.rma_id IS NULL'));
            $orderItems = $collection->load()->getItems();
        }
        $sellers = $this->getSellerDetail($orderItems);
        $rmaItems = $this->getRmaItemsInformation($order, $orderItems);
        $orderDetails = $this->getOrderDetail($order, $orderItems);
        $info["items"] = $rmaItems ?: [];
        $info["sellers"] = $sellers;
        $info["order_details"] = $orderDetails;
        return $info;
    }

    /**
     * @param $item
     * @return array
     * @throws \Exception
     */
    private function getTicketInfo($item)
    {
        $ticketArray = [];
        $checkIsTicket = $this->virtualProduct->checkIsProductTicketTypeByOrderItemId((int)$item->getId());
        if (!$checkIsTicket) {
            return $ticketArray;
        }
        $ticketArray['info'] = $this->virtualProduct->getTicketStatusByOrderItemId((int)$item->getId());
        return $ticketArray;
    }

    /**
     * @param Order $order
     * @param array $orderItems
     * @return array
     */
    private function getRmaItemsInformation(Order $order, array $orderItems)
    {
        $itemInformation = [];
        /**
         * @var $item \Magento\Sales\Model\Order\Item
         */
        foreach ($orderItems as $item) {
            if ($item = $this->getRmaItemInformation($order, $item)) {
                $itemInformation[] = $item;
            };
        }
        return $itemInformation;
    }

    /**
     * @param Order $order
     * @param Order\Item $item
     * @return array
     */
    private function getRmaItemInformation(Order $order, \Magento\Sales\Model\Order\Item $item)
    {
        $product = $item->getProduct();
        // no product found , no seller found ??? need to review this logic with Jean
        if (!$product) {
            return [];
        }
        $details = $this->mpRmaHelper->getSellerDetailsByProductId($product->getId());
        $orderDetails[$details['seller_id']][] = $item->getId();
        $orderDetails = $this->getStatusDetails($orderDetails);
        $orderDetails = $this->setOrderDetailsAddressInfo($order, $orderDetails);
        $orderStatus = $orderDetails[$details['seller_id']]['order_status'] ?? $order->getStatus();
        $orderId = (int)$order->getStoreId();
        $qty = $this->mpRmaHelper->getRmaQty(
            $item->getId(),
            $item->getOrder()->getId(),
            $item->getOrder()->getQtyOrdered(),
            $orderStatus
        );
        return [
            'is_virtual' => (int)$item->getIsVirtual(),
            'ticket_info' => $this->getTicketInfo($item),
            'product_url' => $product->getProductUrl(),
            'product_image' => $this->getProductImageUrl($product, $orderId),
            'price' => $item->getOrder()->formatPrice($item->getPrice()),
            'sku' => $item->getSku(),
            'name' => $item->getName(),
            'flow_status' => $item->getFlowStatus(),
            'qty' => $qty,
            'original_qty' => $item->getQtyOrdered(),
            'id' => $product->getId(),
            'item_id' => $item->getId(),
            'itemId' => $item->getId(),
            'productUrl' => $product->getProductUrl(),
            'productImage' => $this->getProductImageUrl($product),
            "optionHtml" => $this->getOptionsHtml($item),
            "point_money_config_type" => $options['PointMoneyConfigType'] ?? null
        ];
    }

    /**
     * @param $order
     * @param $orderDetails
     * @return mixed
     */
    public function setOrderDetailsAddressInfo($order, $orderDetails)
    {
        $shippingAddress = $order->getShippingAddress();
        $orderDetails['customer_name'] = $order->getCustomerName();
        $orderDetails['shipping_address_street'] = '';
        $orderDetails['shipping_address_city'] = '';
        $orderDetails['shipping_address_region'] = '';
        $orderDetails['phone'] = '';
        if ($shippingAddress) {
            $orderDetails['shipping_address_street'] = $order->getShippingAddress()->getStreet() ?? '';
            $orderDetails['shipping_address_city'] = $order->getShippingAddress()->getCity() ?? '';
            $orderDetails['shipping_address_region'] = $order->getShippingAddress()->getRegion() ?? '';
            $orderDetails['phone'] = $order->getShippingAddress()->getTelephone() ?? '';
        }
        if (is_array($orderDetails['shipping_address_street'])) {
            $orderDetails['shipping_address_street'] = implode(" ", $orderDetails['shipping_address_street']);
        }
        return $orderDetails;
    }

    /**
     * getProductImageUrl
     *
     * @param mixed $product
     * @return string
     */
    private function getProductImageUrl($product, $storeId = 0)
    {
        return $this->imageHelper
            ->init($product, 'product_page_image_small')
            ->setImageFile($product->getImage())
            ->keepAspectRatio(true)
            ->resize(100, 100)
            ->getUrl();
    }

    /**
     * Get Order Item Option Html
     *
     * @param object $orderItem
     *
     * @return string
     */
    private function getOptionsHtml($orderItem)
    {
        return (string)$this->mpRmaHelper->getOptionsHtml($orderItem);
    }

    /**
     * @param $orderDetails
     * @return array
     */
    private function getStatusDetails($orderDetails)
    {
        return $this->mpRmaHelper->getStatusDetails($orderDetails);
    }

    /**
     * @param Order $order
     * @param $itemId
     * @return mixed
     * @throws NoSuchEntityException
     */
    private function getOrderItem(Order $order, $itemId)
    {
        $orderedItems = $order->getAllVisibleItems();
        /**
         * @var $item \Magento\Sales\Model\Order\Item
         */
        foreach ($orderedItems as $item) {
            if ($item->getId() == $itemId) {
                return $item;
            }
        }
        throw new NoSuchEntityException();
    }

    /**
     * Is buyer Loggedin
     *
     * @param boolean $isGuest
     * @return boolean
     */
    public function isBuyerLoggedIn($isGuest)
    {
        $helper = $this->mpRmaHelper;
        if ($isGuest == 1) {
            if (!$this->mpRmaHelper->isGuestLoggedIn()) {
                return false;
            }
        } else {
            if (!$helper->isLoggedIn()) {
                return false;
            }
        }
        return true;
    }
}
