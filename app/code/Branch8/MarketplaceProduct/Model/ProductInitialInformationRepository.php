<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model;

use Branch8\MarketplaceProduct\Api\Data\ProductInitialInformationInterface;
use Branch8\MarketplaceProduct\Api\Data\ProductInitialInformationInterfaceFactory;
use Branch8\MarketplaceProduct\Api\ProductInitialInformationRepositoryInterface;
use Branch8\MarketplaceProduct\Model\ResourceModel\ProductInitialInformation as ProductInitialInformationResource;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Class provides implementation of ProductInitialInformationRepositoryInterface
 */
class ProductInitialInformationRepository implements ProductInitialInformationRepositoryInterface
{
    /**
     * @var array
     */
    private array $registry = [];

    /**
     * @var ProductInitialInformationResource
     */
    private ProductInitialInformationResource $resource;

    /**
     * @var ProductInitialInformationInterfaceFactory
     */
    private ProductInitialInformationInterfaceFactory $productInitialInfoFactory;

    /**
     * ProductInitialInformationRepository constructor.
     *
     * @param ProductInitialInformationResource $resource
     * @param ProductInitialInformationInterfaceFactory $productInitialInfoFactory
     */
    public function __construct(
        ProductInitialInformationResource         $resource,
        ProductInitialInformationInterfaceFactory $productInitialInfoFactory
    ) {
        $this->resource = $resource;
        $this->productInitialInfoFactory = $productInitialInfoFactory;
    }

    /**
     * @inheritdoc
     */
    public function save(ProductInitialInformationInterface $productInitialInfo): ProductInitialInformationInterface
    {
        try {
            $this->resource->save($productInitialInfo);
            $productId = $productInitialInfo->getProductId();
            $this->registry[$productId] = $productInitialInfo;
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        return $productInitialInfo;
    }

    /**
     * @inheritdoc
     */
    public function get(int $productId): ProductInitialInformationInterface
    {
        if (!isset($this->registry[$productId])) {
            $productInitialInfo = $this->productInitialInfoFactory->create();
            $this->resource->load($productInitialInfo, $productId);
            if (!$productInitialInfo->getProductId()) {
                throw new NoSuchEntityException(__('Product initial information with product ID "%1" does not exist.', $productId));
            }
            $this->registry[$productId] = $productInitialInfo;
        }
        return $this->registry[$productId];
    }

    /**
     * @inheritdoc
     */
    public function delete(ProductInitialInformationInterface $productInitialInfo): bool
    {
        try {
            $this->resource->delete($productInitialInfo);
            unset($this->registry[$productInitialInfo->getProductId()]);
        } catch (\Exception $e) {
            if ($productInitialInfo->getProductId()) {
                throw new CouldNotDeleteException(__(
                    'Unable to remove product initial information with product ID %1. Error: %2',
                    [$productInitialInfo->getProductId(), $e->getMessage()]
                ));
            }
            throw new CouldNotDeleteException(__('Unable to remove product initial information. Error: %1', $e->getMessage()));
        }

        return true;
    }
}
