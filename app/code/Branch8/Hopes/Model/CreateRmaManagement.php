<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Branch8\Hopes\Model;

use Branch8\Hopes\Helper\Logger as LoggerHelper;
use Branch8\Hopes\Helper\Log as HopesLog;
use Branch8\Hopes\Helper\Response\Message;
use Branch8\Hopes\Logger\Logger;
use Branch8\Hopes\Model\AbstractModel;
use Branch8\Rma\Helper\RmaRecord;
use Branch8\Rma\Helper\Status as RmaStatus;
use Branch8\Sales\Helper\Order\UpdateOrderStatus as ItemStatus;
use Exception;
use Magento\Sales\Api\Data\ShipmentTrackInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Creditmemo\ItemCreationFactory;
use Magento\Sales\Model\RefundOrder;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory as TrackCollectionFactory;
use \Branch8\Refund\Helper\CreateCreditMemo;
use \Branch8\Rma\Helper\Config\StatusLabel;
use \Branch8\Rma\Helper\Data;
use \Magento\Customer\Model\Customer;
use \Magento\Framework\Webapi\Rest\Request;
use \Magento\Framework\Webapi\Rest\Response;
use \Webkul\MpRmaSystem\Model\DetailsFactory;

class CreateRmaManagement extends AbstractModel implements \Branch8\Hopes\Api\CreateRmaManagementInterface

{
    /**
     * Log type value used by admin multiselect.
     */
    private const LOG_TYPE = 'CreateRmaManagement';


    /** @var \Magento\Framework\Webapi\Rest\Request $request */
    public $request;

    /** @var Branch8\Hopes\Helper\Logger $logger */
    private $logger;

    /** @var /Branch8\Hopes\Helper\Logger $loggerhelper */
    private $loggerhelper;

    /** @var array $result */
    public $result = [];

    /** @var \Magento\Sales\Model\Order\Shipment\TrackFactory $trackFactory */
    protected $trackFactory;

    /** @var  \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory */
    protected $trackingCollection;

    /** @var \Branch8\Rma\Helper\Data $rmaHelper */
    protected $rmaHelper;

    /** @var \Magento\Customer\Model\Customer $customer */
    protected $customer;

    /** @var \Branch8\Rma\Helper\Config\StatusLabel $statusLabel */
    protected $statusLabel;

    /** @var \Webkul\MpRmaSystem\Model\DetailsFactory */
    protected $details;

    /** @var \Branch8\Sales\Helper\Order\UpdateOrderStatus $itemStatus */
    protected $itemStatus;

    /**　@var \Branch8\Rma\Helper\Status $status */
    protected $status;

    /** @var \Magento\Sales\Model\Order\Creditmemo\ItemCreationFactory $itemCreationFactory */
    protected $itemCreationFactory;

    /** @var \Magento\Sales\Api\OrderRepositoryInterface $orderRepository */
    protected $orderRepository;

    /** @var \Magento\Sales\Model\RefundOrder $refundOrder */
    protected $refundOrder;

    /**　@var \Branch8\Rma\Helper\RmaRecord $rmaRecord */
    protected $rmaRecord;

    /** @var \Branch8\Refund\Helper\CreateCreditMemo $createCreditMemo */
    protected $createCreditMemo;

    /**
     * @var HopesLog
     */
    private HopesLog $hopesLog;

    public function __construct(
        Logger $logger,
        LoggerHelper $loggerhelper,
        Request $request,
        Response $response,
        TrackCollectionFactory $collectionFactory,
        Data $rmaHelper,
        Customer $customer,
        RmaStatus $status,
        StatusLabel $statusLabel,
        ItemStatus $itemStatus,
        DetailsFactory $details,
        ItemCreationFactory $itemCreationFactory,
        OrderRepositoryInterface $orderRepository,
        RefundOrder $refundOrder,
        RmaRecord $rmaRecord,
        CreateCreditMemo $createCreditMemo,
        HopesLog $hopesLog
    ) {
        $this->rmaHelper = $rmaHelper;
        $this->trackingCollection = $collectionFactory;
        $this->customer = $customer;
        $this->status = $status;
        $this->statusLabel = $statusLabel;
        $this->itemStatus = $itemStatus;
        $this->details = $details;
        $this->itemCreationFactory = $itemCreationFactory;
        $this->orderRepository = $orderRepository;
        $this->refundOrder = $refundOrder;
        $this->rmaRecord = $rmaRecord;
        $this->createCreditMemo = $createCreditMemo;
        $this->logger = $logger;
        $this->hopesLog = $hopesLog;
        parent::__construct($request, $logger, $loggerhelper, $response);
    }

    /**
     * {@inheritdoc}
     */
    public function postCreateRma()
    {
        $this->hopesLog->log(self::LOG_TYPE, '[Start] CreateRmaManagement Start');

        $shipmentData = $this->request->getBodyParams();

        $this->hopesLog->log(self::LOG_TYPE, '[Request Body]'. json_encode($shipmentData, JSON_UNESCAPED_UNICODE));

        try {
            foreach ($shipmentData as $trackingNumber => $data) {

                $shipment = $this->updateShipmentComment($trackingNumber, $data);

                if (!$shipment) {
                    continue;
                }

                $rma = $this->createRma($shipment->getOrder(), $trackingNumber);

                if (!$rma) {
                    continue;
                }

                $this->result[$trackingNumber] = Message::getReturnMessageList(Message::RETURN_SUCCESS_UPDATE);
            }

        } catch (\Exception $e) {
            $this->hopesLog->logException(self::LOG_TYPE, $e);

            $this->result = Message::getReturnMessageList(Message::RETURN_ERROR_FAILED_CREATE_RMA);
        }

        $this->hopesLog->log(self::LOG_TYPE, '[Response]'. json_encode($this->result, JSON_UNESCAPED_UNICODE));

        $this->setResponse($this->result);
    }
    
    /**
     * createCreditMemo
     *
     * @param  mixed $order
     * @param  string $trackingNumber
     * @return array
     */
    public function createCreditMemo($order, $trackingNumber)
    {
        $result = $this->createCreditMemo->execute(
            $order
        );

        if ($result['error'] == 1) {
            $this->result[$trackingNumber] = Message::getReturnMessageList(Message::RETURN_ERROR_FAILED_CREATE_CREDITMEMO);
        }

        return $result;
    }
    
    /**
     * updateShipmentComment
     *
     * @param  string $trackingNumber
     * @param  array $data
     * @return 
     */
    public function updateShipmentComment($trackingNumber, $data)
    {
        try {
            $tracking = $this->trackingCollection->create()
                ->addFieldToFilter(
                    ShipmentTrackInterface::TRACK_NUMBER,
                    $trackingNumber
                );
            $tracking = $tracking->getLastItem();
            $shipment = $tracking->getShipment();
            $shipment->addComment(json_encode($data, JSON_UNESCAPED_UNICODE));
            $shipment->save();

            $this->result[$trackingNumber] = Message::getReturnMessageList(Message::RETURN_SUCCESS_UPDATE);
            return $shipment;
        } catch (\Exception $e) {
            $this->result[$trackingNumber] = Message::getReturnMessageList(Message::RETURN_ERROR_FAILED_CREATE_SHIPMENT);
            
            $this->hopesLog->logException(self::LOG_TYPE, $e, ['tracking_number' => $trackingNumber, 'data' => $data]);
        }
    }

    /**
     * createRma
     *
     * @param  mixed $shipment
     * @return 
     */
    public function createRma($order, $trackingNumber)
    {
        try {
            $rma = $this->rmaRecord->createByPresco($order);
            return $rma;
        } catch (Exception $e) {
            $this->hopesLog->logException(self::LOG_TYPE, $e, ['tracking_number' => $trackingNumber]);
            
            $this->result[$trackingNumber] = Message::getReturnMessageList(Message::RETURN_ERROR_FAILED_CREATE_RMA);
        }
    }
}
