<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Preorder\Plugin\Webkul\Preorder\Model\IsProductSalableForRequestedQtyCondition;

use Magento\Framework\App\ResourceConnection;
use Magento\InventorySales\Model\IsProductSalableCondition\IsAnySourceItemInStockCondition as IsAnySourceItemInStock;
use Magento\InventorySales\Model\IsProductSalableForRequestedQtyCondition\IsAnySourceItemInStockCondition as IsAnyS;
use Magento\InventorySalesApi\Api\Data\ProductSalabilityErrorInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\ProductSalableResultInterface;
use Magento\InventorySalesApi\Api\Data\ProductSalableResultInterfaceFactory;

/**
 * @inheritdoc
 */
class IsAnySourceItemInStockCondition extends IsAnyS
{
    /**
     * @var IsAnySourceItemInStock
     */
    private $isAnySourceInStockCondition;

    /**
     * @var ProductSalabilityErrorInterfaceFactory
     */
    private $productSalabilityErrorFactory;

    /**
     * @var ProductSalableResultInterfaceFactory
     */
    private $productSalableResultFactory;

    /** @var \Magento\Catalog\Api\ProductRepositoryInterface */
    private $productRepository;

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    protected $isPreorder = [];

    /**
     * @param IsAnySourceItemInStock $isAnySourceInStockCondition
     * @param ProductSalabilityErrorInterfaceFactory $productSalabilityErrorFactory
     * @param ProductSalableResultInterfaceFactory $productSalableResultFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        IsAnySourceItemInStock $isAnySourceInStockCondition,
        ProductSalabilityErrorInterfaceFactory $productSalabilityErrorFactory,
        ProductSalableResultInterfaceFactory $productSalableResultFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        ResourceConnection $resourceConnection
    ) {
        $this->isAnySourceInStockCondition = $isAnySourceInStockCondition;
        $this->productSalabilityErrorFactory = $productSalabilityErrorFactory;
        $this->productSalableResultFactory = $productSalableResultFactory;
        $this->productRepository = $productRepository;
        $this->resourceConnection = $resourceConnection;
        parent::__construct(
            $isAnySourceInStockCondition,
            $productSalabilityErrorFactory,
            $productSalableResultFactory
        );
    }

    /**
     * @inheritdoc
     */
    public function aroundExecute($subject, callable $proceed, string $sku, int $stockId, float $requestedQty): ProductSalableResultInterface
    {
        $errors = [];
        if (!$this->checkIsPreorder($sku)) {
            if (!$this->isAnySourceInStockCondition->execute($sku, $stockId)) {
                $data = [
                    'code' => 'is_any_source_item_in_stock-no_source_items_in_stock',
                    'message' => __('There are no source items with the in stock status')
                ];
                $errors[] = $this->productSalabilityErrorFactory->create($data);
            }
        }
        return $this->productSalableResultFactory->create(['errors' => $errors]);
    }
    /**
     * Check if the product is Preorder or Not
     * @param $productSku
     * @return string
     */
    private function checkIsPreorder($productSku)
    {
        if (isset($this->isPreorder[$productSku])) {
            return $this->isPreorder[$productSku];
        }
        try {
            $connection = $this->resourceConnection->getConnection();
            $tbProduct = $connection->getTableName('catalog_product_entity');
            $tbProductPrO = $connection->getTableName('catalog_product_preorder');
            $bind = [
                'sku' => $productSku,
            ];
            $select = $connection->select()->from(
                ['e' => $tbProduct],
                ['entity_id']
            )->join(
                ['epo' => $tbProductPrO],
                "e.entity_id = epo.product_id AND epo.status = 1",
                []
            )->where(
                'e.sku = :sku'
            );
            $productId = $connection->fetchOne($select, $bind);
            if ($productId) {
                return $this->isPreorder[$productSku] = true;
            } else {
                return $this->isPreorder[$productSku] = false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }
}
