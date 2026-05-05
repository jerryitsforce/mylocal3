<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model;

use Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderRepositoryInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderDetailFactory;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder as ResourceModel;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\InputException;
use Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderExtensionFactory;
use Magento\Framework\Exception\NoSuchEntityException;

class ParentOrderRepository implements ParentOrderRepositoryInterface
{
    /**
     * @var ResourceModel
     */
    private ResourceModel $resourceModel;
    /**
     * @var \Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory
     */
    private ParentOrderFactory $parentOrderFactory;
    /**
     * @var array
     */
    private $registry = [];
    /**
     * @var ParentOrderExtensionFactory|mixed
     */
    private ParentOrderExtensionFactory $parentOrderExtensionFactory;
    private \Branch8\MarketPlaceParentOrder\Model\ParentOrderDetailFactory $detailFactory;

    /**
     * @param \Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory $parentOrderFactory
     * @param ResourceModel $resourceModel
     * @param \Branch8\MarketPlaceParentOrder\Model\ParentOrderDetailFactory $detailFactory
     * @param ParentOrderExtensionFactory|null $parentOrderExtensionFactory
     */
    public function __construct(
        ParentOrderFactory          $parentOrderFactory,
        ResourceModel               $resourceModel,
        ParentOrderDetailFactory    $detailFactory,
        ParentOrderExtensionFactory $parentOrderExtensionFactory = null
    )
    {
        $this->parentOrderExtensionFactory = $parentOrderExtensionFactory ?: ObjectManager::getInstance()
            ->get(\Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderExtensionFactory::class);
        $this->resourceModel = $resourceModel;
        $this->detailFactory = $detailFactory;
        $this->parentOrderFactory = $parentOrderFactory;
    }

    /**
     * @param int $id
     * @return ParentOrderInterface|ParentOrder|mixed
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function get(int $id): ParentOrderInterface
    {
        if (!$id) {
            throw new InputException(__('An ID is needed. Set the ID and try again.'));
        }
        if (!isset($this->registry[$id])) {
            /**
             * @var $entity ParentOrder
             */
            $entity = $this->parentOrderFactory->create()->load($id);
            if (!$entity->getEntityId()) {
                throw new NoSuchEntityException(
                    __("The entity that was requested doesn't exist. Verify the entity and try again.")
                );
            }
            $this->setParentOrderDetail($entity);
            $this->registry[$id] = $entity;
        }
        return $this->registry[$id];
    }

    /**
     * @param string $incrementId
     * @return ParentOrderInterface|ParentOrder|mixed
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function getByIncrementId(string $incrementId)
    {
        if (!$incrementId) {
            throw new InputException(__('An IncrementId is needed. Set the ID and try again.'));
        }
        $detail = $this->detailFactory->create()->load($incrementId, 'increment_id');
        if (!$detail || !$detail->getId()) {
            throw new NoSuchEntityException();
        }
        $id = $detail->getParentId();
        return $this->get((int)$id);
    }

    /**
     * @param ParentOrderInterface $parentOrder
     * @return void
     */
    private function setParentOrderDetail(ParentOrderInterface $parentOrder)
    {
        $extensionAttributes = $parentOrder->getExtensionAttributes();
        if ($extensionAttributes === null) {
            $extensionAttributes = $this->parentOrderExtensionFactory->create();
        }
        /**
         * @var $extensionAttributes \Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderExtension
         */
        $detail = $this->detailFactory->create()->load(
            $parentOrder->getIndexId(), 'parent_id'
        );
        $extensionAttributes->setDetail(
            $detail
        );
        $parentOrder->setExtensionAttributes($extensionAttributes);
    }

    /**
     * @param ParentOrderInterface $parentOrder
     * @return ParentOrderInterface
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function save(ParentOrderInterface $parentOrder)
    {
        try {
            $this->resourceModel->save($parentOrder);
        } catch (\Exception $exception) {
            throw $exception;
        }
        return $parentOrder;
    }
}
