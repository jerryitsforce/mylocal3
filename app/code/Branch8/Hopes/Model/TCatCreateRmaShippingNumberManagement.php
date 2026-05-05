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
use Branch8\Rma\Helper\RmaActions;
use Branch8\Sales\Helper\Order\UpdateOrderStatus;
use Exception;
use \Magento\Framework\Webapi\Rest\Request;
use \Magento\Framework\Webapi\Rest\Response;

class TCatCreateRmaShippingNumberManagement extends AbstractModel implements \Branch8\Hopes\Api\TCatCreateRmaShippingNumberManagementInterface
{
    /**
     * Log type value used by admin multiselect.
     */
    private const LOG_TYPE = 'TCatCreateRmaShippingNumberManagement';

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

    /** @var  \Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collectionfactory */
    protected $trackingCollection;

    /** @var \Branch8\Rma\Helper\RmaActions $rmaActions */
    protected $rmaActions;

    /** @var \Branch8\Sales\Helper\Order\UpdateOrderStatus $updateOrderStatus */
    protected $updateOrderStatus;

    /**
     * @var HopesLog
     */
    private HopesLog $hopesLog;

    public function __construct(
        Logger $logger,
        LoggerHelper $loggerhelper,
        Request $request,
        Response $response,
        RmaActions $rmaActions,
        UpdateOrderStatus $updateOrderStatus,
        HopesLog $hopesLog
    ) {
        $this->rmaActions        = $rmaActions;
        $this->updateOrderStatus = $updateOrderStatus;
        $this->logger            = $logger;
        $this->hopesLog          = $hopesLog;
        parent::__construct($request, $logger, $loggerhelper, $response);
    }

    /**
     * {@inheritdoc}
     */
    public function postTCatCreateRmaShippingNumberManagement()
    {
        $this->hopesLog->log(self::LOG_TYPE, '[Start] TCatCreateRmaShippingNumber Start');

        $shipmentData = $this->request->getBodyParams();

        $this->hopesLog->log(self::LOG_TYPE, '[Request Body]' . json_encode($shipmentData, JSON_UNESCAPED_UNICODE));

        try {
            foreach ($shipmentData as $trackingNumber => $data) {

                try {
                    if (is_string($data)) {
                        throw new Exception('Invalid Data Type. Please check your request params.');
                    }

                    $this->hopesLog->log(self::LOG_TYPE, '[RmaId] ' . $data['RMAID'] . ' -- Tracking number: ' . $trackingNumber);

                    $this->rmaActions->changeStatusByAdmin(
                        $data['RMAID'],
                        \Branch8\Rma\Model\Rma\Status::RETURN_SHIPPING
                    );

                    $status = $this->rmaActions->updateReplaceShippingNumberBySellerOrAdmin(
                        $data['RMAID'],
                        $trackingNumber
                    );

                    $item = $this->rmaActions->getRmaItemCollection($data['RMAID']);

                    foreach ($item as $singleItem) {
                        $this->updateOrderStatus->addItemStatusRecord(
                            $singleItem->getOrderId(), $singleItem, $status);

                        $singleItem->setFlowStatus($status);
                        $singleItem->save();
                    }

                    $this->result[$trackingNumber] = Message::getReturnMessageList(Message::RETURN_SUCCESS_UPDATE);
                } catch (\Exception $e) {
                    $this->hopesLog->logException(self::LOG_TYPE, $e, ['tracking_number' => $trackingNumber, 'data' => $data]);
                    $this->result[$trackingNumber] = Message::getReturnMessageList(Message::RETURN_ERROR_FAILED_CREATE_RMA_SHIPMENT);

                }
            }

        } catch (\Exception $e) {
            $this->hopesLog->logException(self::LOG_TYPE, $e);
            $this->result = Message::getReturnMessageList(Message::RETURN_ERROR_FAILED_CREATE_RMA_SHIPMENT);
        }

        $this->hopesLog->log(self::LOG_TYPE, '[Response]' . json_encode($this->result, JSON_UNESCAPED_UNICODE));
        $this->setResponse($this->result);
    }
}
