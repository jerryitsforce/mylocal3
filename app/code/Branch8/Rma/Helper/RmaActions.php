<?php

namespace Branch8\Rma\Helper;

use Branch8\Rma\Helper\Config\StatusLabel;
use Branch8\Rma\Helper\Data;
use Branch8\Rma\Helper\Status as RmaStatusHelper;
use Branch8\Rma\Model\MarketplaceRmaShippingFactory;
use Branch8\Rma\Model\Rma\Status;
use Branch8\Rma\Model\SenderType;
use Exception;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use \Magento\Sales\Api\OrderItemRepositoryInterface;
use \Magento\Customer\Model\Customer;
use Branch8\HotaiCore\Helper\VirtualProduct;
use Branch8\HotaiCore\Helper\Common as HotaiCoreCommon;
use Branch8\HotaiCore\Model\Order\Status as HotaiStatus;

class RmaActions
{
    /**
     * Log option value for this helper.
     */
    private const LOG_OPTION = 'RmaActions';

    /**
     * @var \Magento\Customer\Model\Url
     */
    protected $url;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $session;

    /**
     * @var \Branch8\Rma\Helper\Data
     */
    protected $mpRmaHelper;

    /**
     * @var \Webkul\MpRmaSystem\Model\DetailsFactory
     */
    protected $details;

    /**
     * @var MessageManagerInterface
     */
    protected $messageManager;

    /** @var \Magento\Sales\Api\OrderItemRepositoryInterface $itemCollectionFactory */
    protected $itemCollectionFactory;

    /** @var \Magento\Sales\Api\OrderItemRepositoryInterface $itemCollectionFactory */
    protected $rmaStatusHelper;

    /** @var \Branch8\Rma\Helper\Config\StatusLabel $statusLabel */
    protected $statusLabel;

    /** @var \Branch8\Rma\Model\MarketplaceRmaShippingFactory $marketplaceRmaShippingFactory */
    protected $marketplaceRmaShippingFactory;

    /** @var \Magento\Customer\Model\Customer $customer */
    protected $customer;

    /** @var \Branch8\HotaiCore\Helper\VirtualProduct $virtualProduct */
    protected $virtualProduct;
    
    /** @var \Branch8\HotaiCore\Helper\Common $hotaiCoreCommon */
    protected $hotaiCoreCommon;

    public function __construct(
        \Magento\Customer\Model\Url $url,
        \Magento\Customer\Model\Session $session,
        \Branch8\Rma\Helper\Data $mpRmaHelper,
        \Webkul\MpRmaSystem\Model\DetailsFactory $details,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        OrderItemRepositoryInterface $itemCollectionFactory,
        RmaStatusHelper $rmaStatusHelper,
        StatusLabel $statusLabel,
        MarketplaceRmaShippingFactory $marketplaceRmaShippingFactory,
        Customer $customer,
        VirtualProduct $virtualProduct,
        HotaiCoreCommon $hotaiCoreCommon
    ) {
        $this->url = $url;
        $this->session = $session;
        $this->mpRmaHelper = $mpRmaHelper;
        $this->details = $details;
        $this->messageManager = $messageManager;
        $this->itemCollectionFactory = $itemCollectionFactory;
        $this->rmaStatusHelper = $rmaStatusHelper;
        $this->statusLabel = $statusLabel;
        $this->marketplaceRmaShippingFactory = $marketplaceRmaShippingFactory;
        $this->customer = $customer;
        $this->virtualProduct = $virtualProduct;
        $this->hotaiCoreCommon = $hotaiCoreCommon;
    }

    public function cancelRmaApplication($rmaId)
    {
        $rma = $this->mpRmaHelper->getRmaDetails($rmaId);
        $status = $rma->getStatus();

        if ($rma->getResolutionType() == $this->mpRmaHelper::RESOLUTION_REFUND) {
            $status = \Branch8\Rma\Model\Rma\Status::RETURN_APPLY_CANCEL;
            $this->updateOrderCancelRmaStatus(
                $this->mpRmaHelper->getOrder($rma->getOrderId()), 
                HotaiStatus::STATUS_RMA_RETURN_CANCEL);
        }

        if ($rma->getResolutionType() == $this->mpRmaHelper::RESOLUTION_REPLACE) {
            $status = \Branch8\Rma\Model\Rma\Status::REPLACE_APPLY_CANCEL;
            $this->updateOrderCancelRmaStatus(
                $this->mpRmaHelper->getOrder($rma->getOrderId()), 
                HotaiStatus::STATUS_RMA_REPLACE_CANCEL);
        }

        $rmaData = ['status' => $status, 'final_status' => Data::FINAL_STATUS_CANCELED];
        $rma->addData($rmaData)->setId($rmaId)->save();

        //update status record
        $comment = __('RMA request canceled');
        $this->rmaStatusHelper->createStatusRecord($status, $rma, $comment);
        $this->mpRmaHelper->saveRmaHistory($rma->getId(), $comment, 2);
        $this->messageManager->addSuccess(__($comment));

        try {
            //TODO
            //$this->mpRmaHelper->sendUpdateRmaEmail(['rma_id' => $rmaId]);
        } catch (\Exception $e) {
            \Magento\Framework\App\ObjectManager::getInstance()
                ->get(\Branch8\Rma\Helper\Log::class)
                ->exception($e, self::LOG_OPTION, __METHOD__, ['rma_id' => $rmaId]);

            $this->messageManager->addError(__($e->getMessage()));
        }

        return $this->statusLabel->getAdminAndSellerPanelRmaStatusTitle($status);
    }

    /**
     * getItemCollection
     *
     * @param  int $itemId
     * @return \Magento\Sales\Api\Data\OrderItemInterface
     */
    public function getItemCollection($itemId)
    {
        $item = $this->itemCollectionFactory->get($itemId);
        return $item;
    }

    /**
     * getRmaItemCollection
     *
     * @param  int $rmaId
     * @return array
     */
    public function getRmaItemCollection($rmaId)
    {
        $rmaItems = $this->mpRmaHelper->getRmaItemsByRmaId($rmaId);

        $itemCol = [];

        foreach($rmaItems as $item) {
            $itemCol[] = $this->getItemCollection($item->getItemId());
        }

        return $itemCol;
    }

    /**
     * changeStatusBySeller
     *
     * @param  int $rmaId
     * @param  string|int $sellerUpdateStatus
     * @return string|int
     */
    public function changeStatusBySeller($rmaId, $sellerUpdateStatus, $extraParams = [])
    {

        list($rma, $status) = $this->changeStatus($rmaId, $sellerUpdateStatus, $extraParams);
        //save history
        $message = $this->statusLabel->getAdminAndSellerPanelRmaStatusTitle($sellerUpdateStatus);
        $statusTitle = $this->statusLabel->getAdminAndSellerPanelRmaStatusTitle($status);

        $this->mpRmaHelper->saveRmaHistory($rmaId,
            $this->resolveRmaHistoryMessage($sellerUpdateStatus,
                $message, $extraParams
            ),
            SenderType::TYPE_SELLER);
        $this->rmaStatusHelper->createStatusRecord($statusTitle, $rma);

        return $statusTitle;
    }

    /**
     * @param $rmaId
     * @param $sellerUpdateStatus
     * @param $extraParams
     * @return array
     */
    public function changeStatus($rmaId, $sellerUpdateStatus, $extraParams = [])
    {
        $rma = $this->details->create()->load($rmaId);
        //變更狀態
        $status = $this->rmaStatusHelper->getNextStatus(
            $sellerUpdateStatus,
            $this->mpRmaHelper->isNaturalPerson($rma)
        );

        // save rma detail
        $rmaData = [];
        $data['rma_id'] = $rmaId;
        $rmaData['seller_status'] = $sellerUpdateStatus;
        $rmaData['status'] = $status;
        if ($extraParams) {
            $rmaData = array_merge($rmaData, $extraParams);
        }
        $rma->addData($rmaData)->setId($rmaId)->save();
        return [$rma, $status];
    }

    /**
     * @param $rmaId
     * @param $newstatus
     * @param $extraParams
     * @return string
     */
    public function changeStatusByAdmin($rmaId, $newstatus, $extraParams = [])
    {
        list($rma, $status) = $this->changeStatus($rmaId, $newstatus, $extraParams);
        //save history
        $message = $this->statusLabel->getAdminAndSellerPanelRmaStatusTitle($newstatus);
        $statusTitle = $this->statusLabel->getAdminAndSellerPanelRmaStatusTitle($status);
        $this->mpRmaHelper->saveRmaHistory($rmaId,
            $this->resolveRmaHistoryMessage($newstatus, $message, $extraParams),
            SenderType::TYPE_ADMIN
        );
        $this->rmaStatusHelper->createStatusRecord($statusTitle, $rma);
        return $statusTitle;
    }

    /**
     * @param $status
     * @param $message
     * @param $extraParams
     * @return \Magento\Framework\Phrase
     */
    private function resolveRmaHistoryMessage($status, $message, $extraParams = [])
    {
        $message = __($message);
        if (in_array($status, Status::declinedStatus()) && isset($extraParams['decline_reason_detail'])) {
            $message = __($message)->render() . " | " . __('Reason:') . $extraParams['decline_reason_detail'];
        }
        return $message;
    }
    /**
     * updateReplaceShippingNumberBySeller
     *
     * @param  int $rmaId
     * @param  string $shippingNumber
     * @return string | int
     */
    public function updateReplaceShippingNumberBySellerOrAdmin($rmaId, $shippingNumber, $shippingCarrier = null, $senderType = 1)
    {
        $rma = $this->details->create()->load($rmaId);
        $status = $this->rmaStatusHelper->getNextStatus(
            $rma->getStatus(),
            $this->mpRmaHelper->isNaturalPerson($rma)
        );

        // save rma detail
        $rmaData = [];
        $rmaData['seller_status'] = $status;
        $rmaData['status'] = $status;
        $rma->addData($rmaData)->setId($rmaId)->save();

        //save history
        $statusTitle = $this->statusLabel->getAdminAndSellerPanelRmaStatusTitle($status);
        $message = $statusTitle;
        $shippingCarrierMsg = $shippingCarrier ? __("Carrier: ") . $shippingCarrier . ' ' : '';
        $shippingNumberMsg = __("Shipping Number: ") . $shippingNumber;
        $message = __($message) . "  "  . $shippingCarrierMsg . $shippingNumberMsg;
        $this->mpRmaHelper->saveRmaHistory($rmaId, $message, $senderType);
        $this->rmaStatusHelper->createStatusRecord($statusTitle, $rma);
        $this->setShippingNumberRecord($rma, $shippingNumber, $statusTitle, $shippingCarrier);

        return $statusTitle;
    }


    /**
     * setShippingNumberRecord
     *
     * @param  mixed $rma
     * @param  string $shippingNumber
     * @param  string $status
     * @return void
     */
    public function setShippingNumberRecord($rma, $shippingNumber, $status, $shippingCarrier = null)
    {

        /** \Branch8\Rma\Model\MarketplaceRmaShipping */
        $record = $this->marketplaceRmaShippingFactory->create();
        $record->setParentId($rma->getId());
        $record->setShippingNumber($shippingNumber);
        $record->setShippingCarrier($shippingCarrier);
        $record->setStatus($status);
        $record->save();
    }

    /**
     * changeResolutionType
     *
     * @param  int $rmaId
     */
    public function changeResolutionType($rmaId, $type)
    {
        $rma = $this->details->create()->load($rmaId);
        $rmaData = [];
        $rmaData['resolution_type'] = $type;
        $rma->addData($rmaData)->setId($rmaId)->save();
    }

    /**
     * saveActionRecord
     *
     * @param  mixed $updateStatus
     * @param  mixed $rmaId
     * @return void
     */
    public function saveActionRecord($updateStatus, $rmaId) {
        $message = $this->statusLabel->getAdminAndSellerPanelRmaStatusTitle($updateStatus);
        $this->mpRmaHelper->saveRmaHistory($rmaId, __($message), 1);
    }

    /**
     * changeStatusByRefundCron
     *
     * @param  int $rmaId
     * @param  strng $sellerUpdateStatus
     * @param  array $extraParams
     * @return string
     */
    public function changeStatusByRefundCron($rmaId, $sellerUpdateStatus, $extraParams = [])
    {
        list($rma, $status) = $this->changeStatus($rmaId, $sellerUpdateStatus, $extraParams);

        //save history
        $message = $this->statusLabel->getAdminAndSellerPanelRmaStatusTitle($sellerUpdateStatus);
        $statusTitle = $this->statusLabel->getAdminAndSellerPanelRmaStatusTitle($status);

        $this->mpRmaHelper->saveRmaHistory($rmaId,
            $this->resolveRmaHistoryMessage($sellerUpdateStatus,
                $message, $extraParams
            ),
            SenderType::TYPE_ADMIN);

        $this->rmaStatusHelper->createStatusRecord($statusTitle, $rma);



        return $statusTitle;
    }

    /**
     * changeStatusBySalesCron
     *
     * @param  int $itemId
     * @param  strng $sellerUpdateStatus
     * @param  array $extraParams
     * @return string
     */
    public function changeItemStatusBySalesCron($itemId, $sellerUpdateStatus, $extraParams = [])
    {
        $rmaId = $this->mpRmaHelper->getRmaIdByItemId($itemId);

        if (is_null($rmaId) || empty($rmaId)) {
            throw new Exception('There is no rma.');
        }

        list($rma, $status) = $this->changeStatus($rmaId, $sellerUpdateStatus, $extraParams);

        //save history
        $message = $this->statusLabel->getAdminAndSellerPanelRmaStatusTitle($sellerUpdateStatus);
        $statusTitle = $this->statusLabel->getAdminAndSellerPanelRmaStatusTitle($status);

        $this->mpRmaHelper->saveRmaHistory($rmaId,
            $this->resolveRmaHistoryMessage($sellerUpdateStatus,
                $message, $extraParams
            ),
            SenderType::TYPE_ADMIN);

        $this->rmaStatusHelper->createStatusRecord($statusTitle, $rma);

        return $statusTitle;
    }

    public function cancelTicket($rmaId) {
        $items = $this->getRmaItemCollection($rmaId);

        \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Branch8\Rma\Helper\Log::class)
            ->info('---- Rma Actions - Cancel Ticket', self::LOG_OPTION);

        foreach($items as $singleItem) {
            try {
                $itemId = $singleItem->getItemId();
                \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Branch8\Rma\Helper\Log::class)
                    ->info("---- itemId: {$itemId}", self::LOG_OPTION);
                
                $this->virtualProduct->cancelTickets((int) $itemId );
            } catch(Exception $e) {
                \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Branch8\Rma\Helper\Log::class)
                    ->exception($e, self::LOG_OPTION, __METHOD__, ['rma_id' => $rmaId, 'item_id' => $itemId ?? null]);

            }
        }
    }

    public function updateRmaRecordData($rmaId, $rmaData)
    {
        $rma = $this->mpRmaHelper->getRmaDetails($rmaId);
        $rma->addData($rmaData)->setId($rmaId)->save();
    }

    public function updateOrderCancelRmaStatus($order, $status){
        $order->setRmaStatus($status);
        $order->addCommentToStatusHistory('Cancel Rma Application. Rma Status changes to '. $status );
        $order->save();
    }

}
