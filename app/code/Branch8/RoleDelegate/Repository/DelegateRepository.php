<?php
namespace Branch8\RoleDelegate\Repository;

use Branch8\RoleDelegate\Api\DelegateRepositoryInterface;
use Branch8\RoleDelegate\Api\Data\DelegateInterface;
use Branch8\RoleDelegate\Model\DelegateFactory;
use Branch8\RoleDelegate\Model\ResourceModel\Delegate as Resource;
use Branch8\RoleDelegate\Model\ResourceModel\Delegate\CollectionFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Branch8\RoleDelegate\Model\Source\DelegateStatus;

class DelegateRepository implements DelegateRepositoryInterface
{
    /**
     * @var array
     */
    private array $registry = [];

    /**
     * @var DelegateFactory
     */
    private $factory;

    /**
     * @var Resource
     */
    private $resource;

    /**
    * @var CollectionFactory
    */
    private $collectionFactory;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * Constructor
     *
     * @param DelegateFactory $factory
     * @param Resource $resource
     * @param CollectionFactory $collectionFactory
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        DelegateFactory $factory,
        Resource $resource,
        CollectionFactory $collectionFactory,
        TimezoneInterface $timezone
    ){
        $this->factory = $factory;
        $this->resource = $resource;
        $this->collectionFactory = $collectionFactory;
        $this->timezone = $timezone;
    }

    public function save(DelegateInterface $delegate): DelegateInterface
    {
        try {
            $this->resource->save($delegate);
            $id = $delegate->getId();
            $this->registry[$id] = $delegate;
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $delegate;
    }

    public function getById(int $id): DelegateInterface
    {
        if (!isset($this->registry[$id])) {
            $model = $this->factory->create();
            $this->resource->load($model, $id);
            if (!$model->getId()) {
                throw new NoSuchEntityException(__('Delegate with ID "%1" does not exist.', $id));
            }
            $this->registry[$id] = $model;
        }

        return $this->registry[$id];
    }

    public function getActiveForUser(int $userId, ?\DateTime $date = null): DelegateInterface
    {
        $date = $date ?: new \DateTime('now', new \DateTimeZone('UTC'));
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('delegate_user_id', $userId);
        $collection->addFieldToFilter('status', ['in' => [DelegateStatus::ACTIVE]]);
        $collection->addFieldToFilter('start_at', ['lteq' => $date->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s')]);
        $collection->addFieldToFilter('end_at', ['gteq' => $date->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s')]);
        $collection->setPageSize(1);
        $collection->setCurPage(1);

        return $collection->getFirstItem();
    }

    public function hasOverlap(int $userId, int $delegateId, string $startAt, string $endAt): bool
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('user_id', $userId);
        $collection->addFieldToFilter('status', ['nin' => [DelegateStatus::CANCELLED]]);
        $collection->addFieldToFilter('start_at', ['lteq' => $endAt]);
        $collection->addFieldToFilter('end_at', ['gteq' => $startAt]);
        if ($delegateId) {
            $collection->addFieldToFilter('id', ['neq' => $delegateId]);
        }

        return (bool)$collection->getSize();
    }

    /**
     * Check overlapping delegation for delegate user
     *
     * @param int $delegateUserId
     * @param int $delegateId
     * @param string $startAt
     * @param string $endAt
     * @return bool
     */
    public function hasOverlapForDelegateUser(
        int $delegateUserId,
        int $delegateId,
        string $startAt,
        string $endAt
    ): bool {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('delegate_user_id', $delegateUserId);
        $collection->addFieldToFilter('status', ['nin' => [DelegateStatus::CANCELLED]]);
        $collection->addFieldToFilter('start_at', ['lteq' => $endAt]);
        $collection->addFieldToFilter('end_at', ['gteq' => $startAt]);

        if ($delegateId) {
            $collection->addFieldToFilter('id', ['neq' => $delegateId]);
        }

        return (bool)$collection->getSize();
    }

    public function cancel(int $id, int $actorId): void
    {
        $model = $this->getById($id);
        $model->setData('status', DelegateStatus::CANCELLED);
        $this->resource->save($model);
        // TODO: write history record
    }

    public function search(array $filters = []): array
    {
        $collection = $this->collectionFactory->create();
        if (!empty($filters['status'])) {
            $collection->addFieldToFilter('status', $filters['status']);
        }
        if (!empty($filters['user_id'])) {
            $collection->addFieldToFilter('user_id', $filters['user_id']);
        }
        return $collection->getItems();
    }
}
