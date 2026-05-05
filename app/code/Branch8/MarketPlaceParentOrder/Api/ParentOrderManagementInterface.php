<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Api;

use Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

interface ParentOrderManagementInterface
{
    /**
     * @param ParentOrderInterface $parentOrder
     * @return true
     * @throws LocalizedException
     */
    public function hold(ParentOrderInterface $parentOrder);

    /**
     * @param ParentOrderInterface $parentOrder
     * @return bool
     */
    public function canHold(ParentOrderInterface $parentOrder);

    /**
     * @param ParentOrderInterface $parentOrder
     * @return bool
     */
    public function canUnHold(ParentOrderInterface $parentOrder);

    /**
     * @param ParentOrderInterface $parentOrder
     * @return bool
     */
    public function unhold(ParentOrderInterface $parentOrder);

    /**
     * @param ParentOrderInterface $parentOrder
     * @return bool
     */
    public function isPaymentReview(ParentOrderInterface $parentOrder);

    /**
     * @param ParentOrderInterface $parentOrder
     * @return true
     * @throws LocalizedException
     */
    public function cancel(ParentOrderInterface $parentOrder);

    /**
     * @param ParentOrderInterface $parentOrder
     * @return bool
     */
    public function canCancel(ParentOrderInterface $parentOrder);

    /**
     * @param ParentOrderInterface $parentOrder
     * @return mixed
     */
    public function sendConfirmationEmail(ParentOrderInterface $parentOrder);

    /**
     * @param ParentOrder $parentOrder
     * @return string
     */
    public function getPaymentHtml(ParentOrder $parentOrder);

    /**
     * @param ParentOrder $parentOrder
     * @return array
     */
    public function getTotals(ParentOrder $parentOrder);

    /**
     * @param ParentOrder $parentOrder
     * @return DataObject
     */
    public function getTotalByKey(ParentOrder $parentOrder, string $code);

    /**
     * @param int $parentId
     * @param array $subOrderIds
     * @return mixed
     */
    public function assignSubordersToParentOrder(int $parentId, array $subOrderIds = []);

    /**
     * @param ParentOrderInterface $parentOrder
     * @return bool
     */
    public function canReorder(ParentOrderInterface $parentOrder);

    /**
     * @param ParentOrderInterface $parentOrder
     * @return bool
     */
    public function canEdit(ParentOrderInterface $parentOrder);

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param string $status
     * @return mixed
     */
    public function updateSubOrderStatus(\Magento\Sales\Model\Order $order, string $status);

    /**
     * @param string $comment
     * @return mixed
     */
    public function setComment(string $comment);

    /**
     * @param ParentOrderInterface $parentOrder
     * @param string $comment
     * @param string|null $status
     * @param bool $notifyCustomer
     * @param bool $isVisibleOnFront
     * @return mixed
     */
    public function saveStatusHistory(
        ParentOrderInterface $parentOrder,
        string               $comment,
        string               $status = null,
        bool                 $notifyCustomer = false,
        bool                 $isVisibleOnFront = false
    );

    /**
     * @param ParentOrder $parentOrder
     * @param $newStatus
     * @return mixed
     */
    public function changeStatus(ParentOrder $parentOrder, $newStatus);
}
