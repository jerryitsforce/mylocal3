<?php
declare(strict_types=1);

namespace Branch8\ProductCertification\Model;

use Branch8\ProductCertification\Model\ResourceModel\CertificationType as ResourceModel;
use Branch8\ProductCertification\Model\ResourceModel\CertificationType\CollectionFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * CertificationType repository — thin layer for save/load/delete
 */
class CertificationTypeRepository
{
    /**
     * @var ResourceModel
     */
    private ResourceModel $resourceModel;

    /**
     * @var CertificationTypeFactory
     */
    private CertificationTypeFactory $modelFactory;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * @param ResourceModel $resourceModel
     * @param CertificationTypeFactory $modelFactory
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        ResourceModel $resourceModel,
        CertificationTypeFactory $modelFactory,
        CollectionFactory $collectionFactory
    ) {
        $this->resourceModel     = $resourceModel;
        $this->modelFactory      = $modelFactory;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Load by ID
     * @param int $id
     * @return CertificationType
     * @throws NoSuchEntityException
     */
    public function getById(int $id): CertificationType
    {
        $model = $this->modelFactory->create();
        $this->resourceModel->load($model, $id);
        if (!$model->getId()) {
            throw new NoSuchEntityException(__('Certification type with ID %1 not found.', $id));
        }
        return $model;
    }

    /**
     * Save
     * @param CertificationType $model
     * @return CertificationType
     * @throws CouldNotSaveException
     */
    public function save(CertificationType $model): CertificationType
    {
        try {
            $this->resourceModel->save($model);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }
        return $model;
    }

    /**
     * Delete
     * @param CertificationType $model
     * @return void
     */
    public function delete(CertificationType $model): void
    {
        $this->resourceModel->delete($model);
    }

    /**
     * Get active certification types filtered by a list of category IDs
     * @param array $categoryIds
     * @return CertificationType[]
     */
    public function getActiveByCategoryIds(array $categoryIds): array
    {
        /** @var \Branch8\ProductCertification\Model\ResourceModel\CertificationType\Collection $collection */
        $collection = $this->collectionFactory->create()->addActiveFilter();
        $collection->setOrder('sort_order', 'ASC');

        $result = [];
        /** @var CertificationType $item */
        foreach ($collection as $item) {
            $assignedIds = $item->getCategoryIds();
            // Check if ANY of $categoryIds are in $assignedIds
            if (array_intersect($categoryIds, $assignedIds)) {
                $result[] = $item;
            }
        }
        return $result;
    }
}
