<?php

namespace Branch8\CatalogCustom\Plugin\Checkout\CustomerData;

class DefaultItemPlugin
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {
        $this->productRepository = $productRepository;
    }
    public function afterGetItemData(\Magento\Checkout\CustomerData\DefaultItem $subject, $result)
    {
        $product = $this->productRepository->getById($result['product_id']);
        return \array_merge(
            ['individual_product' => $product->getData('individual_product')],
            $result
        );
    }
}
