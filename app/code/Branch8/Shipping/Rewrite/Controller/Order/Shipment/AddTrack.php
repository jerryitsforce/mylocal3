<?php

declare(strict_types=1);

namespace Branch8\Shipping\Rewrite\Controller\Order\Shipment;

use Magento\Backend\App\Action;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Sales\Api\Data\ShipmentTrackInterfaceFactory;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader;

class AddTrack extends \Magento\Shipping\Controller\Adminhtml\Order\Shipment\AddTrack
{
    /**
     * @var ShipmentRepositoryInterface
     */
    private ShipmentRepositoryInterface $shipmentRepository;

    /**
     * @var ShipmentTrackInterfaceFactory
     */
    private ShipmentTrackInterfaceFactory $trackFactory;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * AddTrack constructor.
     *
     * @param Action\Context $context
     * @param ShipmentLoader $shipmentLoader
     * @param ShipmentRepositoryInterface|null $shipmentRepository
     * @param ShipmentTrackInterfaceFactory|null $trackFactory
     * @param SerializerInterface|null $serializer
     */
    public function __construct(
        Action\Context                $context,
        ShipmentLoader                $shipmentLoader,
        ShipmentRepositoryInterface   $shipmentRepository = null,
        ShipmentTrackInterfaceFactory $trackFactory = null,
        SerializerInterface           $serializer = null
    ) {
        parent::__construct(
            $context,
            $shipmentLoader,
            $shipmentRepository,
            $trackFactory,
            $serializer
        );
        $this->shipmentRepository = $shipmentRepository ?: ObjectManager::getInstance()
            ->get(ShipmentRepositoryInterface::class);
        $this->trackFactory = $trackFactory ?: ObjectManager::getInstance()
            ->get(ShipmentTrackInterfaceFactory::class);
        $this->serializer = $serializer ?: ObjectManager::getInstance()
            ->get(SerializerInterface::class);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        try {
            $carrier = $this->getRequest()->getPost('carrier');
            $number = $this->getRequest()->getPost('number');
            $title = $this->getRequest()->getPost('title');
            $lpName = $this->getRequest()->getPost('logistics_provider_name'); // override here
            $lcUrl = $this->getRequest()->getPost('logistics_company_url'); // override here

            if (empty($carrier) || empty($title)) {
                throw new LocalizedException(__('Please specify a carrier.'));
            }
            if (empty($number)) {
                throw new LocalizedException(__('Please enter a tracking number.'));
            }

            $this->shipmentLoader->setOrderId($this->getRequest()->getParam('order_id'));
            $this->shipmentLoader->setShipmentId($this->getRequest()->getParam('shipment_id'));
            $this->shipmentLoader->setShipment($this->getRequest()->getParam('shipment'));
            $this->shipmentLoader->setTracking($this->getRequest()->getParam('tracking'));
            $shipment = $this->shipmentLoader->load();
            if ($shipment) {
                if ($title === '其他：自行填寫名稱') {
                    $title = $lpName ?: $title;
                }
                $track = $this->trackFactory->create()->setNumber(
                    $number
                )->setCarrierCode(
                    $carrier
                )->setTitle(
                    $title
                )->setLogisticsCompanyUrl(
                    $lcUrl
                );
                $shipment->addTrack($track);
                $this->shipmentRepository->save($shipment);

                $this->_view->loadLayout();
                $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Shipments'));
                $response = $this->_view->getLayout()->getBlock('shipment_tracking')->toHtml();
            } else {
                $response = [
                    'error' => true,
                    'message' => __('We can\'t initialize shipment for adding tracking number.'),
                ];
            }
        } catch (LocalizedException $e) {
            $response = ['error' => true, 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            $response = ['error' => true, 'message' => __('Cannot add tracking number.')];
        }

        if (\is_array($response)) {
            $response = $this->serializer->serialize($response);

            return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setJsonData($response);
        }

        return $this->resultFactory->create(ResultFactory::TYPE_RAW)->setContents($response);
    }
}
