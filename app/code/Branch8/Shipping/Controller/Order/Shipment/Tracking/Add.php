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
use Magento\Sales\Model\Order\Shipment\TrackFactory;

class Add extends Action
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
        $response = ['success' => false, 'message' => __('Unable to add tracking.')];

        try {
            $orderId = $this->getRequest()->getParam('order_id');
            $number = $this->getRequest()->getPost('number');
            $title = $this->getRequest()->getPost('title');
            $lpName = $this->getRequest()->getPost('logistics_provider_name');
            $lcUrl = $this->getRequest()->getPost('logistics_company_url');

            if (empty($title)) {
                throw new LocalizedException(__('Please specify a carrier.'));
            }
            if (empty($number)) {
                throw new LocalizedException(__('Please enter a tracking number.'));
            }

            if ($title === '其他：自行填寫名稱') {
                $title = $lpName ?: $title;
            }

            $shipment = $this->createShipment->execute((int)$orderId);
            $items = $shipment->getItems();
            $tracking = $this->trackFactory->create()
                ->setCarrierCode('custom')
                ->setNumber($number)
                ->setTitle($title)
                ->setLogisticsCompanyUrl($lcUrl);
            $shipment->addTrack($tracking);
            $this->shipmentRepository->save($shipment);
            $names = array_map(function ($item) {
                return $item->getName();
            }, $items);
            $response = [
                'success' => true,
                'message' => __('New Shipment created successfully for items "%s"',join(',', $names)),
                'trackingData' => TrackingToArray::convert($tracking)
            ];
        } catch (\Exception $e) {
            $response['message'] = $e->getMessage();
        }

        return $result->setData($response);
    }
}
