<?php

declare(strict_types=1);

namespace Branch8\Shipping\Controller\Order\Shipment\Tracking;

use Branch8\Shipping\Model\CreateShipment;
use Branch8\Shipping\Model\TrackingToArray;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory as ResultJsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order\Shipment\Track;
use Magento\Sales\Model\Order\Shipment\TrackFactory;

class Save extends Action
{
    /**
     * @var ResultJsonFactory
     */
    private ResultJsonFactory $resultJsonFactory;

    /**
     * @var TrackFactory
     */
    private TrackFactory $trackFactory;

    /**
     * @var CreateShipment
     */
    private CreateShipment $createShipment;

    /**
     * @var ShipmentRepositoryInterface
     */
    private ShipmentRepositoryInterface $shipmentRepository;

    /**
     * Constructor.
     *
     * @param Context $context
     * @param ResultJsonFactory $resultJsonFactory
     * @param TrackFactory $trackFactory
     * @param CreateShipment $createShipment
     * @param ShipmentRepositoryInterface $shipmentRepository
     */
    public function __construct(
        Context                     $context,
        ResultJsonFactory           $resultJsonFactory,
        TrackFactory                $trackFactory,
        CreateShipment              $createShipment,
        ShipmentRepositoryInterface $shipmentRepository

    )
    {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->trackFactory = $trackFactory;
        $this->createShipment = $createShipment;
        $this->shipmentRepository = $shipmentRepository;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $response = ['success' => false, 'message' => __('Unable to save tracking.')];

        try {
            $trackId = $this->getRequest()->getParam('id');
            $title = $this->getRequest()->getPost('carrier');
            $code = $this->getRequest()->getPost('tracking_number');
            $lpName = $this->getRequest()->getPost('logistics_provider_name');
            $lcUrl = $this->getRequest()->getPost('logistics_company_url');
            if (!$trackId) {
                throw new LocalizedException(__('Missing tracking ID.'));
            }
            $track = $this->_objectManager->create(Track::class)->load($trackId);
            if (!$track->getId()) {
                throw new LocalizedException(__('Cannot load track with retrieving identifier right now.'));
            }
            if ($title === '其他：自行填寫名稱') {
                $title = $lpName ?: $title;
            }
            $update = [
                'track_number' => $code,
                'title' => $title,
                'logistics_company_url' => $lcUrl
            ];
            foreach ($update as $field => $value) {
                $track->setData($field, $value);
            }
            $track->save();
            $response = [
                'success' => true,
                'message' => __('Tracking saved successfully.'),
                'trackingData' => TrackingToArray::convert($track)
            ];
        } catch (\Exception $e) {
            $response['message'] = $e->getMessage();
        }

        return $result->setData($response);
    }
}
