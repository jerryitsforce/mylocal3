<?php

namespace Branch8\TicketApi\Helper\CheckHandler;

use Branch8\TicketApi\Helper\CheckHandler\BaseHandler;
use Branch8\TicketApi\Model\Api\Data\CheckDataFactory as DataFactory;
use Branch8\TicketApi\Helper\Api;
use Branch8\TicketApi\Model\TicketApiMerchant;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Api\OrderItemRepositoryInterface as OrderItemRepository;
use Branch8\GeneralNonNotifyTicket\Model\GeneralNonNotifyTicketBatchSetting as BatchSetting;
use Branch8\GeneralNonNotifyTicket\Model\GeneralNonNotifyTicketBatchSettingRepository as BatchSettingRepository;
use Branch8\GeneralNonNotifyTicket\Model\GeneralNonNotifyTicketRecord as Model;
use Branch8\GeneralNonNotifyTicket\Model\GeneralNonNotifyTicketRecordRepository as ModelRepository;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommonHelper;
use Branch8\HotaiCore\Model\Ticket\Status as TicketStatus;
use Branch8\TicketApi\Helper\CheckHandler\Const\SerialNoStatus;
use Branch8\TicketApi\Helper\Data as DataHelper;
use Webkul\Marketplace\Model\ResourceModel\Seller\CollectionFactory as SellerCollectionFactory;

class GeneralNonNotify extends BaseHandler
{
    /** @var DataFactory */
    protected $dataFactory;

    /** @var Api */
    protected $apiHelper;

    /** @var SellerCollectionFactory */
    protected $sellerCollectionFactory;

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
        SellerCollectionFactory $sellerCollectionFactory,
        ModelRepository $modelRepository,
        BatchSettingRepository $batchSettingRepository,
        OrderItemRepository $orderItemRepository,
        HotaiCoreCommonHelper $hotaiCoreCommonHelper,
        DataHelper $dataHelper,
    ) {
        $this->apiHelper               = $apiHelper;
        $this->sellerCollectionFactory = $sellerCollectionFactory;
        $this->modelRepository         = $modelRepository;
        $this->batchSettingRepository  = $batchSettingRepository;
        $this->orderItemRepository     = $orderItemRepository;
        $this->hotaiCoreCommonHelper   = $hotaiCoreCommonHelper;
        $this->dataHelper              = $dataHelper;

        parent::__construct($dataFactory);
    }

    /**
     * @throws \Exception
     * @return void
     */
    public function handleCheckTicket($merchant): void
    {
        $serialNo = $this->apiHelper->getSerialNo();

        $collection = $this->modelRepository->getRecordBySerialNumberAndSellerIdsString(
            $serialNo,
            $merchant->getSellerIds()
        );

        // If ticket not exist.
        if ($collection->getSize() == 0) {
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

        // multi records error
        if ($collection->getSize() > 1) {
            $this->returnCode = Api::RETURN_CODE_NOT_EXIST;
            $this->returnMsg  = Api::RETURN_MESSAGE_TICKET_CHECK_UNEXPECTED_CONDITION;

            $this->returnData->setIsUsable(false);
            $this->returnData->setSerialNoStatus(SerialNoStatus::NOT_EXIST);
            $this->returnData->setProductNo("");
            $this->returnData->setProductNameM("");
            $this->returnData->setProductNameS("");
            $this->returnData->setExpiry("");

            return;
        }

        /** @var Model $model */
        $model = $collection->getFirstItem();

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

    protected function searchTargetRecordForGeneralNonNotifyTicket(TicketApiMerchant $merchant): ?Model
    {
        $serialNo = $this->apiHelper->getSerialNo();
        $sellerId = 0;

        // If merchant "allow_all_seller" is enable, then we need to use request parameter "usedStoreNo" to find "seller_id"
        // otherwise just use the seller which bind to merchant.
        if ($merchant->getAllowAllSeller()) {
            $sellerCollection = $this->sellerCollectionFactory->create();
            $sellerCollection->addFieldToFilter("seller_code", $this->apiHelper->getUsedStoreNo());
            $sellerCollection->load();

            $seller = $sellerCollection->getFirstItem();

            $sellerId = (!is_null($seller) && !empty($seller->getId())) ? $seller->getData("seller_id") : 0;
        } else {
            $sellerId = $merchant->getSellerId();
        }

        return $this->modelRepository->getRecordBySerialNumberAndSellerId($serialNo, $sellerId);
    }
}
