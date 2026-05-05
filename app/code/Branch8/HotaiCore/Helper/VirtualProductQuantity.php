<?php

namespace Branch8\HotaiCore\Helper;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory;
use Magento\InventoryApi\Api\SourceItemsSaveInterface;

class VirtualProductQuantity
{
    /** @var ProductRepositoryInterface */
    protected $productRepository;

    /** @var DefaultSourceProviderInterface */
    protected $defaultSourceProvider;

    /** @var SourceItemInterfaceFactory */
    protected $sourceItemFactory;

    /** @var SourceItemsSaveInterface */
    protected $sourceItemsSaveInterface;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        DefaultSourceProviderInterface $defaultSourceProvider,
        SourceItemInterfaceFactory $sourceItemFactory,
        SourceItemsSaveInterface $sourceItemsSaveInterface
    ) {
        $this->productRepository        = $productRepository;
        $this->defaultSourceProvider    = $defaultSourceProvider;
        $this->sourceItemFactory        = $sourceItemFactory;
        $this->sourceItemsSaveInterface = $sourceItemsSaveInterface;
    }

    public function updateQuantity(int $productId, int $quantity): void
    {
        $product = $this->productRepository->getById($productId);

        $status = $quantity ? SourceItemInterface::STATUS_IN_STOCK : SourceItemInterface::STATUS_OUT_OF_STOCK;

        /** @var SourceItemInterface $sourceItem */
        $sourceItem = $this->sourceItemFactory->create();
        $sourceItem->setSourceCode($this->defaultSourceProvider->getCode());
        $sourceItem->setSku($product->getSku());
        $sourceItem->setQuantity($quantity);
        $sourceItem->setStatus($status);
        $this->sourceItemsSaveInterface->execute([$sourceItem]);
    }
}
