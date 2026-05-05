<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Hopes\Model;

use Branch8\Hopes\Helper\Logger as LoggerHelper;
use Branch8\Hopes\Logger\Logger;
use Branch8\Hopes\Model\AbstractModel;
use Branch8\Hopes\Helper\Log as HopesLog;
use \Magento\Framework\Webapi\Rest\Request;
use \Magento\Framework\Webapi\Rest\Response;
use Magento\Sales\Api\Data\ShipmentTrackInterface;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Track\CollectionFactory as TrackCollectionFactory;
use Branch8\Hopes\Helper\Response\Message;

class UpdateShipmentSrpManagement extends AbstractModel implements \Branch8\Hopes\Api\UpdateShipmentSrpManagementInterface
{
    /**
     * Log type value used by admin multiselect.
     */
    private const LOG_TYPE = 'UpdateShipmentSrpManagement';

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

    /**
     * @var HopesLog
     */
    private HopesLog $hopesLog;

    /**
     * @param Logger $logger
     * @param LoggerHelper $loggerhelper
     * @param Request $request
     * @param Response $response
     * @param TrackCollectionFactory $collectionFactory
     * @param HopesLog $hopesLog
     */
    public function __construct(
        Logger $logger,
        LoggerHelper $loggerhelper,
        Request $request,
        Response $response,
        TrackCollectionFactory $collectionFactory,
        HopesLog $hopesLog
    ) {
        $this->trackingCollection = $collectionFactory;
        $this->logger = $logger;
        $this->hopesLog = $hopesLog;
        parent::__construct($request, $logger, $loggerhelper, $response);
    }

    /**
     * {@inheritdoc}
     */
    public function postUpdateShipmentSrp()
    {
        $this->hopesLog->log(self::LOG_TYPE, '[Start] UpdateShipmentSRP Start');

        $shipmentData = $this->request->getBodyParams();

        $this->hopesLog->log(self::LOG_TYPE, '[Request Body]' . json_encode($shipmentData, JSON_UNESCAPED_UNICODE));

        try {
            foreach ($shipmentData as $trackingNumber => $data) {
                try {
                    $tracking = $this->trackingCollection->create()
                        ->addFieldToFilter(
                            ShipmentTrackInterface::TRACK_NUMBER, 
                            $trackingNumber
                    );
                    $tracking = $tracking->getLastItem();
                    $shipment = $tracking->getShipment();
                    $shipment->addComment('ErrorCode: '. $data['ErrorCode']);
                    $shipment->save();
                    $this->result[$trackingNumber] = Message::getReturnMessageList(Message::RETURN_SUCCESS_UPDATE);
                    $this->hopesLog->log(self::LOG_TYPE, '[Tracking Number] ' . $trackingNumber. ' -- Updated.');
                } catch (\Exception $e) {
                    $this->result[$trackingNumber] = Message::getReturnMessageList(Message::RETURN_ERROR_FAILED_UPDATE_SHIPMENT);
                    $this->hopesLog->log(self::LOG_TYPE, '[Tracking Number] ' . $trackingNumber. ' -- '. $e->getMessage());
                    $this->hopesLog->logException(self::LOG_TYPE, $e, ['tracking_number' => $trackingNumber, 'data' => $data]);
                }
            }

        } catch (\Exception $e) {
            $this->hopesLog->logException(self::LOG_TYPE, $e);
            $this->result = Message::getReturnMessageList(Message::RETURN_ERROR_FAILED_UPDATE_SHIPMENT);
        }

        $this->hopesLog->log(self::LOG_TYPE, '[Response]' . json_encode($this->result, JSON_UNESCAPED_UNICODE));        
        
        $this->setResponse($this->result);
    }
}

