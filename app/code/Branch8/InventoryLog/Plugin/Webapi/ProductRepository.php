<?php

namespace Branch8\InventoryLog\Plugin\Webapi;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;

class ProductRepository
{
    public function __construct(
        public \Magento\Framework\Registry $registry,
        public \Elgentos\InventoryLog\Helper\Data $helper
    ) {}

    public function beforeSave(
        ProductRepositoryInterface $subject,
        ProductInterface $product,
        $saveOptions = false
    ): array {
        if (!$this->helper->isModuleEnabled()) {
            return [$product, $saveOptions];
        }

        if (!$this->registry->registry(\Elgentos\InventoryLog\Helper\Data::MOVEMENT_SECTION)) {
            $this->registry->register(
                \Elgentos\InventoryLog\Helper\Data::MOVEMENT_SECTION,
                \Elgentos\InventoryLog\Helper\Data::WEBAPI_STOCK_UPDATE
            );
        }

        return [$product, $saveOptions];
    }
}
