<?php

declare(strict_types=1);

namespace Branch8\Shipping\Controller\Adminhtml\Order\Shipment\Tracking;

use Branch8\Shipping\Model\CreateShipment;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory as ResultJsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order\Shipment\TrackFactory;

class Add extends Action implements HttpPostActionInterface
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
    ) {
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
            $tracking = $this->trackFactory->create()
                ->setCarrierCode('custom')
                ->setNumber($number)
                ->setTitle($title)
                ->setLogisticsCompanyUrl($lcUrl);
            $shipment->addTrack($tracking);
            $this->shipmentRepository->save($shipment);

            $response = [
                'success' => true,
                'message' => __('Tracking added successfully.'),
                'trackingData' => [
                    'id' => $tracking->getId(),
                    'carrier' => $title,
                    'number' => $number
                ]
            ];
        } catch (\Exception $e) {
            $response['message'] = $e->getMessage();
        }

        return $result->setData($response);
    }

    /**
     * @inheritDoc
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Branch8_Shipping::shipment_tracking_add');
    }
}
