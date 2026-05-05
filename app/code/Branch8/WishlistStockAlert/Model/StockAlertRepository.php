<?php

namespace Branch8\WishlistStockAlert\Model;

use Branch8\WishlistStockAlert\Api\StockAlertRepositoryInterface;
use Branch8\WishlistStockAlert\Api\Data\StockAlertInterface;
use Branch8\WishlistStockAlert\Model\ResourceModel\StockAlert as StockAlertResource;
use Branch8\WishlistStockAlert\Model\ResourceModel\StockAlert\CollectionFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\CouldNotDeleteException;

/**
 *
 */
class StockAlertRepository implements StockAlertRepositoryInterface
{
    protected $resource;
    protected $stockAlertFactory;
    protected $collectionFactory;

    /**
     * @param StockAlertResource $resource
     * @param StockAlertFactory $stockAlertFactory
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        StockAlertResource $resource,
        StockAlertFactory  $stockAlertFactory,
        CollectionFactory  $collectionFactory
    )
    {
        $this->resource = $resource;
        $this->stockAlertFactory = $stockAlertFactory;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @param StockAlertInterface $stockAlert
     * @return StockAlertInterface
     * @throws CouldNotSaveException
     */
    public function save(StockAlertInterface $stockAlert)
    {
        try {
            $this->resource->save($stockAlert);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }
        return $stockAlert;
    }

    /**
     * @param $alertId
     * @return StockAlertInterface
     * @throws NoSuchEntityException
     */
    public function getById($alertId)
    {
        $stockAlert = $this->stockAlertFactory->create();
        $this->resource->load($stockAlert, $alertId);
        if (!$stockAlert->getId()) {
            throw new NoSuchEntityException(__('Stock alert with id "%1" does not exist.', $alertId));
        }
        return $stockAlert;
    }

    /**
     * @param $wishlistItemId
     * @return \Magento\Framework\DataObject
     */

    public function getByWishlistItemId($wishlistItemId)
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('wishlist_item_id', $wishlistItemId);
        return $collection->getFirstItem();
    }

    /**
     * @param StockAlertInterface $stockAlert
     * @return true
     * @throws CouldNotDeleteException
     */
    public function delete(StockAlertInterface $stockAlert)
    {
        try {
            $this->resource->delete($stockAlert);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__($e->getMessage()));
        }
        return true;
    }

    /**
     * @param $alertId
     * @return true
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById($alertId)
    {
        return $this->delete($this->getById($alertId));
    }

    /**
     * @return StockAlertResource\Collection
     */
    public function getPendingAlerts()
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('was_out_of_stock_when_added', 1)
            ->addFieldToFilter('notification_sent', 0);
        return $collection;
    }

}
