<?php

declare(strict_types=1);

namespace Branch8\Report\Model;

use Branch8\Report\Api\Data\ProductChangeLogInterface;
use Branch8\Report\Api\Data\ProductChangeLogInterfaceFactory;
use Branch8\Report\Api\ProductChangeLogRepositoryInterface;
use Branch8\Report\Model\ResourceModel\ProductChangeLog as ProductChangeLogResource;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class provides implementation of SellerActionHistoryRepositoryInterface
 */
class ProductChangeLogRepository implements ProductChangeLogRepositoryInterface
{
    /**
     * @var array
     */
    private array $registry = [];

    /**
     * @var ProductChangeLogResource
     */
    private ProductChangeLogResource $productChangeLogResource;

    /**
     * @var ProductChangeLogInterfaceFactory
     */
    private ProductChangeLogInterfaceFactory $productChangeLogFactory;

    /**
     * Constructor.
     *
     * @param ProductChangeLogResource $productChangeLogResource
     * @param ProductChangeLogInterfaceFactory $productChangeLogFactory
     */
    public function __construct(
        ProductChangeLogResource $productChangeLogResource,
        ProductChangeLogInterfaceFactory $productChangeLogFactory
    ) {
        $this->productChangeLogResource = $productChangeLogResource;
        $this->productChangeLogFactory = $productChangeLogFactory;
    }

    /**
     * @inheritdoc
     */
    public function save(ProductChangeLogInterface $productChangeLog): ProductChangeLogInterface
    {
        try {
            $this->productChangeLogResource->save($productChangeLog);
            $id = $productChangeLog->getId();
            $this->registry[$id] = $productChangeLog;
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        return $productChangeLog;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $id): ProductChangeLogInterface
    {
        if (!isset($this->registry[$id])) {
            $productChangeLog = $this->productChangeLogFactory->create();
            $this->productChangeLogResource->load($productChangeLog, $id);
            if (!$productChangeLog->getId()) {
                throw new NoSuchEntityException(__('Product change log with ID "%1" does not exist.', $id));
            }
            $this->registry[$id] = $productChangeLog;
        }
        return $this->registry[$id];
    }
}
