<?php
declare(strict_types=1);

namespace Branch8\Marketplace\Model\Actions;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Model\OrderRepository;

/**
 *
 */
class BuildMarketPlaceSaleListRecord
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resource;
    /**
     * @var OrderRepository
     */
    private OrderRepository $orderRepository;

    /**
     * @param ResourceConnection $resource
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        ResourceConnection $resource,
        OrderRepository    $orderRepository
    )
    {
        $this->orderRepository = $orderRepository;
        $this->resource = $resource;
    }

    /**
     * @param array $ids
     * @return void
     * @throws \Magento\Framework\Exception\InputExceptionQ
     */
    public function execute(array $ids)
    {
        $records = [];
        foreach ($ids as $id) {
            try {
                $orderRecords = $this->getOrderRecord($id);
                $records = array_merge($records, $orderRecords);
            } catch (NoSuchEntityException $e) {
                continue;
            }
        }
        if ($records) {
            $this->resource->getConnection()->insertOnDuplicate('marketplace_saleslist', $records, [
                'mageproduct_id',
                'order_id',
                'order_item_id',
                'parent_item_id',
                'magerealorder_id',
                'magequantity',
                'seller_id',
                'cpprostatus',
                'paid_status',
                'magepro_name',
                'magepro_price',
                'total_amount',
                'total_tax',
                'total_commission',
                'actual_seller_amount',
                'commission_rate',
            ]);
        }
    }

    /**
     * @param $id
     * @return array
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\InputException
     */
    private function getOrderRecord($id)
    {
        $records = [];
        $order = $this->orderRepository->get($id);
        foreach ($order->getAllVisibleItems() as $item) {
            $records[] = $this->basicRecord($item);
        }
        return $records;
    }

    /**
     * @param \Magento\Sales\Model\Order\Item $item
     * @return array
     */
    private function basicRecord(\Magento\Sales\Model\Order\Item $item)
    {
        return [
            'mageproduct_id' => $item->getProductId(),
            'order_id' => $item->getOrderId(),
            'order_item_id' => $item->getItemId(),
            'parent_item_id' => $item->getParentItemId(),
            'magerealorder_id' => $item->getOrder()->getIncrementId(),
            'magequantity' => $item->getQtyOrdered(),
            'seller_id' => $item->getData('seller_id'),
            'cpprostatus' => 0,
            'paid_status' => 1,
            'magepro_name' => $item->getProduct() ? $item->getProduct()->getName() : $item->getName(),
            'magepro_price' => $item->getPriceInclTax(),
            'total_amount' => $item->getProductId(),
            'total_tax' => '',
            'total_commission' => '',
            'actual_seller_amount' => '',
            'commission_rate' => 0
        ];
    }
}
