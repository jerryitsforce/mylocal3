<?php

namespace Branch8\TicketApi\Helper;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Sales\Model\Order\Item as OrderItem;

class Data
{
    /** @var ProductRepositoryInterface */
    protected $productRepository;

    public function __construct(
        ProductRepositoryInterface $productRepository
    ) {
        $this->productRepository = $productRepository;
    }

    public function getProductNameMByOrderItem(OrderItem $orderItem): null|string
    {
        return $orderItem->getName();
    }

    public function getProductNameSByOrderItem(OrderItem $orderItem): null|string
    {
        return "";
    }

    public function getProductNameMByProductId(int $productId): null|string
    {
        $product = $this->productRepository->getById($productId);

        return $product->getName();
    }

    public function getProductNameSByProductId(int $productId): null|string
    {
        return "";
    }

    public function getSellPriceByOrderItem(OrderItem $orderItem): null|int
    {
        return (int) ($orderItem->getRowTotalInclTax() - $orderItem->getDiscountAmount());
    }

    public function getSellPointByOrderItem(OrderItem $orderItem): null|string
    {
        return $orderItem->getData("row_total_point_used") ?? 0;
    }

    public function getTotalSellAmountByOrderItem(OrderItem $orderItem): null|int
    {
        $pointUsed = $this->getSellPointByOrderItem($orderItem);

        return (int) ($orderItem->getRowTotalInclTax() + $pointUsed);
    }

    public function getProductSellPriceByOrderItem(OrderItem $orderItem): null|int
    {
        return (int) $orderItem->getPriceInclTax();
    }

    public function getOrderAmountByOrderItem(OrderItem $orderItem): null|int
    {
        return (int) $orderItem->getQtyOrdered();
    }

    public function formatExpiry(string $useEndTime): null|string
    {
        return date("Ymd", strtotime($useEndTime));
    }
}
