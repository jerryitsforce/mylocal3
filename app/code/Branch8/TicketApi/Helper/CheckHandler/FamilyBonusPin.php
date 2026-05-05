<?php

namespace Branch8\TicketApi\Helper\CheckHandler;

use Branch8\TicketApi\Helper\CheckHandler\BaseHandler;
use Branch8\TicketApi\Model\Api\Data\CheckDataFactory as DataFactory;
use Branch8\TicketApi\Helper\Api;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Api\OrderItemRepositoryInterface as OrderItemRepository;
use Branch8\FamilyBonusPin\Model\FamilyBonusPinBatchSetting as BatchSetting;
use Branch8\FamilyBonusPin\Model\FamilyBonusPinBatchSettingRepository as BatchSettingRepository;
use Branch8\FamilyBonusPin\Model\FamilyBonusPinTicketRecord as Model;
use Branch8\FamilyBonusPin\Model\FamilyBonusPinTicketRecordRepository as ModelRepository;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;
use Branch8\TicketApi\Helper\CheckHandler\Const\SerialNoStatus;
use Branch8\TicketApi\Helper\Data as DataHelper;

class FamilyBonusPin extends BaseHandler
{
    /** @var DataFactory */
    protected $dataFactory;

    /** @var Api */
    protected $apiHelper;

    /** @var ModelRepository */
    protected $modelRepository;

    /** @var BatchSettingRepository */
    protected $batchSettingRepository;

    /** @var OrderItemRepository */
    protected $orderItemRepository;

    /** @var HotaiCoreCommonHelper */
    protected $hotaiCoreCommonHelper;

    /** @var DataHelper */
    protected $dataHelper;

    public function __construct(
        DataFactory $dataFactory,
        Api $apiHelper,
        ModelRepository $modelRepository,
        BatchSettingRepository $batchSettingRepository,
        OrderItemRepository $orderItemRepository,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        DataHelper $dataHelper,
    ) {
        $this->apiHelper              = $apiHelper;
        $this->modelRepository        = $modelRepository;
        $this->batchSettingRepository = $batchSettingRepository;
        $this->orderItemRepository    = $orderItemRepository;
        $this->hotaiCoreCommonHelper  = $hotaiCoreCommonHelper;
        $this->dataHelper             = $dataHelper;

        parent::__construct($dataFactory);
    }

    /**
     * @throws \Exception
     * @return void
     */
    public function handleCheckTicket(): void
    {
        $serialNo = $this->apiHelper->getSerialNo();
        $model    = $this->modelRepository->getRecordBySerialNumber($serialNo);

        // If ticket not exist.
        if (!$model) {
            $this->returnCode = Api::RETURN_CODE_NOT_EXIST;
            $this->returnMsg  = Api::RETURN_MESSAGE_NOT_EXIST;

            $this->returnData->setIsUsable(false);
            $this->returnData->setSerialNoStatus(SerialNoStatus::NOT_EXIST);
            $this->returnData->setProductNo("");
            $this->returnData->setProductNameM("");
            $this->returnData->setProductNameS("");
            $this->returnData->setExpiry("");

            return;
        }

        // Ticket exists, so it should have batch setting related to it.
        /** @var BatchSetting $batchSetting */
        $batchSetting = $this->batchSettingRepository->getById($model->getBatchSettingId());

        // If ticket not sold yet.
        if ($this->checkTicketUnsold($model)) {
            $this->returnCode = Api::RETURN_CODE_UNABLE_TO_USE_YET;
            $this->returnMsg  = Api::RETURN_MESSAGE_UNABLE_TO_USE_YET;

            $this->returnData->setIsUsable(false);
            $this->returnData->setSerialNoStatus(SerialNoStatus::UNSOLD);
            $this->returnData->setProductNo($batchSetting->getBatchCode());
            $this->returnData->setProductNameM($this->dataHelper->getProductNameMByProductId((int) $model->getBelongToProductId()));
            $this->returnData->setProductNameS($this->dataHelper->getProductNameSByProductId((int) $model->getBelongToProductId()));
            $this->returnData->setExpiry("");

            return;
        }

        // Ticket sold, so it should have sales order item related to it.
        /** @var OrderItem $orderItem */
        $orderItem = $this->orderItemRepository->get($model->getSalesOrderItemId());

        // If ticket is used.
        if ($this->checkTicketAlreadyUsed($model)) {
            $this->returnCode = Api::RETURN_CODE_USED_ALREADY;
            $this->returnMsg  = Api::RETURN_MESSAGE_USED_ALREADY;

            $this->returnData->setIsUsable(false);
            $this->returnData->setSerialNoStatus(SerialNoStatus::USED);
            $this->returnData->setProductNo($batchSetting->getBatchCode());
            $this->returnData->setProductNameM($this->dataHelper->getProductNameMByOrderItem($orderItem));
            $this->returnData->setProductNameS($this->dataHelper->getProductNameSByOrderItem($orderItem));
            $this->returnData->setExpiry($this->dataHelper->formatExpiry($model->getUseEndTime()));

            return;
        }

        // If ticket can't be used yet(what's the SerialNoStatus?).
        if ($this->checkIfTicketUnableToUseYet($model)) {
            $this->returnCode = Api::RETURN_CODE_UNABLE_TO_USE_YET;
            $this->returnMsg  = Api::RETURN_MESSAGE_UNABLE_TO_USE_YET;

            $this->returnData->setIsUsable(false);
            $this->returnData->setSerialNoStatus(SerialNoStatus::DATE_INVALID);
            $this->returnData->setProductNo($batchSetting->getBatchCode());
            $this->returnData->setProductNameM($this->dataHelper->getProductNameMByOrderItem($orderItem));
            $this->returnData->setProductNameS($this->dataHelper->getProductNameSByOrderItem($orderItem));
            $this->returnData->setExpiry($this->dataHelper->formatExpiry($model->getUseEndTime()));

            return;
        }

        // If ticket is over due.
        if ($this->checkIfTicketOverDue($model)) {
            $this->returnCode = Api::RETURN_CODE_OVER_DUE;
            $this->returnMsg  = Api::RETURN_MESSAGE_OVER_DUE;

            $this->returnData->setIsUsable(false);
            $this->returnData->setSerialNoStatus(SerialNoStatus::DATE_INVALID);
            $this->returnData->setProductNo($batchSetting->getBatchCode());
            $this->returnData->setProductNameM($this->dataHelper->getProductNameMByOrderItem($orderItem));
            $this->returnData->setProductNameS($this->dataHelper->getProductNameSByOrderItem($orderItem));
            $this->returnData->setExpiry($this->dataHelper->formatExpiry($model->getUseEndTime()));

            return;
        }

        // If ticket is useable.
        if ($this->checkIfTicketUseable($model)) {
            $this->returnCode = Api::RETURN_CODE_SUCCESS;
            $this->returnMsg  = Api::RETURN_MESSAGE_SUCCESS;

            $this->returnData->setIsUsable(true);
            $this->returnData->setSerialNoStatus(SerialNoStatus::USEABLE);
            $this->returnData->setProductNo($batchSetting->getBatchCode());
            $this->returnData->setProductNameM($this->dataHelper->getProductNameMByOrderItem($orderItem));
            $this->returnData->setProductNameS($this->dataHelper->getProductNameSByOrderItem($orderItem));
            $this->returnData->setExpiry($this->dataHelper->formatExpiry($model->getUseEndTime()));

            $this->returnData->setSellPrice($this->dataHelper->getSellPriceByOrderItem($orderItem));
            $this->returnData->setSellPoint($this->dataHelper->getSellPointByOrderItem($orderItem));
            $this->returnData->setTotalSellAmount($this->dataHelper->getTotalSellAmountByOrderItem($orderItem));
            $this->returnData->setProductSellPrice($this->dataHelper->getProductSellPriceByOrderItem($orderItem));
            $this->returnData->setOrderAmount($this->dataHelper->getOrderAmountByOrderItem($orderItem));

            return;
        }

        // Does not match any condition above, something unexpected happened.
        throw new \Exception(Api::RETURN_MESSAGE_TICKET_CHECK_UNEXPECTED_CONDITION);
    }

    protected function checkTicketUnsold(Model $model): bool
    {
        $status = $model->getStatus();

        return $status == TicketStatus::STATUS_IMPORTED || $status == TicketStatus::STATUS_ALLOCATED;
    }

    protected function checkTicketAlreadyUsed(Model $model): bool
    {
        $status = $model->getStatus();

        return $status == TicketStatus::STATUS_USED;
    }

    protected function checkIfTicketUnableToUseYet(Model $model): bool
    {
        $status       = $model->getStatus();
        $useStartTime = $model->getUseStartTime();

        if (empty($useStartTime) || strtotime($useStartTime) < 0) {
            return false;
        }

        return ($status == TicketStatus::STATUS_UNUSED) && (time() < strtotime($useStartTime));
    }

    protected function checkIfTicketOverDue(Model $model): bool
    {
        $status     = $model->getStatus();
        $useEndTime = $model->getUseEndTime();

        if (empty($useEndTime) || strtotime($useEndTime) < 0) {
            return false;
        }

        $overDueCond1 = ($status == TicketStatus::STATUS_UNUSED) && time() > strtotime($useEndTime);
        $overDueCond2 = $status == TicketStatus::STATUS_OVER_DUE;

        return $overDueCond1 || $overDueCond2;
    }

    protected function checkIfTicketUseable(Model $model): bool
    {
        $status = $model->getStatus();

        $cond1 = $status == TicketStatus::STATUS_UNUSED;
        $cond2 = !$this->checkIfTicketUnableToUseYet($model);
        $cond3 = !$this->checkIfTicketOverDue($model);

        return $cond1 && $cond2 && $cond3;
    }
}
