<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Observer;

use Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderRepositoryInterface;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrderDetail;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * See \Webkul\Mpsplitorder\Model\AdminQuoteManagement
 */
class CancelOldParentOrder implements ObserverInterface
{
    private ParentOrderManagementInterface $parentOrderManagement;

    private ParentOrderRepositoryInterface $parentOrderRepository;

    /**
     * @param ParentOrderManagementInterface $parentOrderManagement
     * @param ParentOrderRepositoryInterface $parentOrderRepository
     */
    public function __construct(
        ParentOrderManagementInterface $parentOrderManagement,
        ParentOrderRepositoryInterface $parentOrderRepository
    )
    {
        $this->parentOrderManagement = $parentOrderManagement;
        $this->parentOrderRepository = $parentOrderRepository;
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        $oldParentOrder = $this->getParentOrder(
            $observer->getData('old_parent_order_id')
        );
        $newSplitOrder = $observer->getData('new_parent');
        if (empty($oldParentOrder) || empty($newSplitOrder)) {
            return;
        }
        $newParentOrder = $this->getParentOrder($newSplitOrder);
        if ($oldParentOrder && $oldParentOrder->getEntityId()) {
            $oldParentOrder->getDetail()->setParentRelationNewId(
                (int)$newParentOrder->getEntityId()
            );
            $oldParentOrder->getDetail()->setParentRelationNewRealId(
                $newParentOrder->getDetail()->getIncrementId()
            );
            $this->parentOrderManagement->cancel($oldParentOrder);
            $this->getCoreSession()->unsOldParentOrderId();
        }
    }

    /**
     * @param $id
     * @return ParentOrderInterface|false
     */
    private function getParentOrder($id)
    {
        try {
            return $this->parentOrderRepository->get((int)$id);
        } catch (NoSuchEntityException $exception) {
            return false;
        }
    }

    /**
     * @return \Magento\Framework\Session\SessionManager|mixed
     */
    private function getCoreSession()
    {
        return ObjectManager::getInstance()->get(\Magento\Framework\Session\SessionManager::class);
    }
}
