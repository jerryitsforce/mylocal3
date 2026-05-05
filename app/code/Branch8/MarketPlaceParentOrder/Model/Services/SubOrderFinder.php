<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\Services;

use Branch8\MarketPlaceParentOrder\Helper\Log;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\Exception\NoSuchEntityException;
use Webkul\Marketplace\Model\ResourceModel\Orders\CollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\OrderRepository;

class  SubOrderFinder
{
    const NO_SELlER = 'no_seller';
    private $instanceById = [];

    private $orderRepository;

    private $customerRepository;

    private Log $log;

    private CollectionFactory $marketPlaceOrderCollectionFactory;
    private OrderCollectionFactory $orderCollectionFactory;

    /**
     * @param CollectionFactory $marketPlaceOrderCollectionFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param OrderRepository $orderRepository
     * @param CustomerRepository $customerRepository
     * @param Log $log
     */
    public function __construct(
        CollectionFactory      $marketPlaceOrderCollectionFactory,
        OrderCollectionFactory $orderCollectionFactory,
        OrderRepository        $orderRepository,
        CustomerRepository     $customerRepository,
        Log                   $log
    )
    {
        $this->orderRepository = $orderRepository;
        $this->marketPlaceOrderCollectionFactory = $marketPlaceOrderCollectionFactory;
        $this->customerRepository = $customerRepository;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->log = $log;
    }

    /**
     * @param ParentOrder $parentOrder
     * @return mixed
     */
    public function find(
        ParentOrder $parentOrder
    )
    {
        if (isset($this->instanceById[$parentOrder->getIndexId()])) {
            return $this->instanceById[$parentOrder->getIndexId()];
        }
        try {
            $suborders = $parentOrder->getSuborderIds();
        } catch (\Exception $e) {
            $suborders = [];
        }
        $this->instanceById[$parentOrder->getIndexId()] = [];
        if ($suborders) {
            $this->instanceById[$parentOrder->getIndexId()] = [];
            /**
             * @var $marketPlaceOrders \Webkul\Marketplace\Model\ResourceModel\Orders\Collection
             */
            $marketPlaceOrders = $this->marketPlaceOrderCollectionFactory->create()
                ->addFieldToFilter('order_id', ['in' => $suborders]);
            $noSellerOrders = [];
            $groupBySeller = [];
            if ($marketPlaceOrders->getSize()) {
                $groupOrders = [];
                $sellerOrders = [];
                foreach ($marketPlaceOrders as $marketPlaceOrder) {
                    $sellerOrders[] = $marketPlaceOrder->getOrderId();
                    if ($order = $this->getOrder($marketPlaceOrder->getOrderId())) {
                        $groupOrders[$marketPlaceOrder->getSellerId()][] = $order;
                    }

                }
                foreach ($groupOrders as $sellerId => $orders) {
                    try {
                        $seller = $this->getSeller($sellerId);
                        $groupBySeller[$sellerId] = ['seller' => $seller, 'orders' => $orders];
                    } catch (NoSuchEntityException $exception) {
                        $this->log->logException(
                            'SubOrderFinder',
                            $exception,
                            ['seller_id' => $sellerId]
                        );
                        foreach ($orders as $order) {
                            $noSellerOrders[] = $order->getId();
                        }
                    }
                }
                if ($noSellerOrders) {
                    $remains = [];
                    foreach ($noSellerOrders as $noSellerOrder) {
                        if ($order = $this->getOrder($noSellerOrder)) {
                            $remains[] = $order;
                        }
                    }
                    if ($remains) {
                        $groupBySeller[self::NO_SELlER] = [
                            'seller' => null,
                            'orders' => $remains
                        ];
                    }
                }
            } else {
                $orders = $this->orderCollectionFactory->create()->addFieldToFilter('entity_id', ['in' => $suborders]);
                $remains = [];
                foreach ($orders as $order) {
                    $remains[] = $order;
                }
                if ($remains) {
                    $groupBySeller[self::NO_SELlER] = [
                        'seller' => null,
                        'orders' => $remains
                    ];
                }
            }
            $this->instanceById[$parentOrder->getIndexId()] = $groupBySeller;
        }
        return $this->instanceById[$parentOrder->getIndexId()];
    }

    /**
     * @param $id
     * @return \Magento\Sales\Api\Data\OrderInterface|void
     */
    private function getOrder($id)
    {
        try {
            return $this->orderRepository->get(
                $id
            );
        } catch (\Exception $exception) {
            return;
        }
    }

    /**
     * @param $sellerId
     * @return \Magento\Customer\Api\Data\CustomerInterface
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getSeller($sellerId)
    {
        return $this->customerRepository->getById($sellerId);
    }
}
