<?php
declare(strict_types=1);

namespace HotaiConnected\ManualInvoice\Model;

use HotaiConnected\ManualInvoice\Api\Data\ManualInvoiceInterface;
use HotaiConnected\ManualInvoice\Api\Data\ManualInvoiceInterfaceFactory;
use HotaiConnected\ManualInvoice\Api\ManualInvoiceRepositoryInterface;
use HotaiConnected\ManualInvoice\Model\ResourceModel\ManualInvoice as ManualInvoiceResourceModel;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class ManualInvoiceRepository implements ManualInvoiceRepositoryInterface
{
    /**
     * @var ManualInvoiceResourceModel
     */
    private $resource;

    /**
     * @var ManualInvoiceInterfaceFactory
     */
    private $manualInvoiceFactory;

    /**
     * @param ManualInvoiceResourceModel $resource
     * @param ManualInvoiceInterfaceFactory $manualInvoiceFactory
     */
    public function __construct(
        ManualInvoiceResourceModel $resource,
        ManualInvoiceInterfaceFactory $manualInvoiceFactory
    ) {
        $this->resource = $resource;
        $this->manualInvoiceFactory = $manualInvoiceFactory;
    }

    /**
     * @inheritdoc
     */
    public function save(ManualInvoiceInterface $manualInvoice): ManualInvoiceInterface
    {
        try {
            $this->resource->save($manualInvoice);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__('Could not save manual invoice: %1', $exception->getMessage()));
        }
        return $manualInvoice;
    }

    /**
     * @inheritdoc
     */
    public function getById(int $entityId): ManualInvoiceInterface
    {
        $manualInvoice = $this->manualInvoiceFactory->create();
        $this->resource->load($manualInvoice, $entityId);
        if (!$manualInvoice->getEntityId()) {
            throw new NoSuchEntityException(__('Manual invoice with id "%1" does not exist.', $entityId));
        }
        return $manualInvoice;
    }

    /**
     * @inheritdoc
     */
    public function getByOrderId(int $orderId): ?ManualInvoiceInterface
    {
        $manualInvoice = $this->manualInvoiceFactory->create();
        $this->resource->load($manualInvoice, $orderId, ManualInvoiceInterface::ORDER_ID);
        
        if (!$manualInvoice->getEntityId()) {
            return null;
        }
        
        return $manualInvoice;
    }

    /**
     * @inheritdoc
     */
    public function delete(ManualInvoiceInterface $manualInvoice): bool
    {
        try {
            $this->resource->delete($manualInvoice);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__('Could not delete manual invoice: %1', $exception->getMessage()));
        }
        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $entityId): bool
    {
        return $this->delete($this->getById($entityId));
    }
}