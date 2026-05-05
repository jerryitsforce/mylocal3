<?php

declare(strict_types=1);

namespace Branch8\Shipping\Rewrite\Controller\Order;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Sales\Api\Data\ShipmentItemCreationInterface;
use Magento\Sales\Api\Data\ShipmentItemCreationInterfaceFactory;
use Magento\Sales\Api\Data\ShipmentTrackCreationExtensionInterfaceFactory;
use Magento\Sales\Api\Data\ShipmentTrackCreationInterface;
use Magento\Sales\Api\Data\ShipmentTrackCreationInterfaceFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order\ShipmentDocumentFactory;

class ShipmentLoader extends \Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader
{
    /**
     * @var ManagerInterface
     */
    private ManagerInterface $messageManager;

    /**
     * @var Registry
     */
    private Registry $registry;

    /**
     * @var ShipmentRepositoryInterface
     */
    private ShipmentRepositoryInterface $shipmentRepository;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var ShipmentDocumentFactory
     */
    private ShipmentDocumentFactory $documentFactory;

    /**
     * @var ShipmentTrackCreationInterfaceFactory
     */
    private ShipmentTrackCreationInterfaceFactory $trackFactory;

    /**
     * @var ShipmentItemCreationInterfaceFactory
     */
    private ShipmentItemCreationInterfaceFactory $itemFactory;

    /**
     * @var ShipmentTrackCreationExtensionInterfaceFactory
     */
    private ShipmentTrackCreationExtensionInterfaceFactory $trackCreationExtensionFactory;

    /**
     * ShipmentLoader constructor.
     *
     * @param ManagerInterface $messageManager
     * @param Registry $registry
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param ShipmentDocumentFactory $documentFactory
     * @param ShipmentTrackCreationInterfaceFactory $trackFactory
     * @param ShipmentItemCreationInterfaceFactory $itemFactory
     * @param ShipmentTrackCreationExtensionInterfaceFactory $trackCreationExtensionFactory
     * @param array $data
     */
    public function __construct(
        ManagerInterface                               $messageManager,
        Registry                                       $registry,
        ShipmentRepositoryInterface                    $shipmentRepository,
        OrderRepositoryInterface                       $orderRepository,
        ShipmentDocumentFactory                        $documentFactory,
        ShipmentTrackCreationInterfaceFactory          $trackFactory,
        ShipmentItemCreationInterfaceFactory           $itemFactory,
        ShipmentTrackCreationExtensionInterfaceFactory $trackCreationExtensionFactory,
        array                                          $data = []
    ) {
        parent::__construct(
            $messageManager,
            $registry,
            $shipmentRepository,
            $orderRepository,
            $documentFactory,
            $trackFactory,
            $itemFactory,
            $data
        );
        $this->messageManager = $messageManager;
        $this->registry = $registry;
        $this->shipmentRepository = $shipmentRepository;
        $this->orderRepository = $orderRepository;
        $this->documentFactory = $documentFactory;
        $this->trackFactory = $trackFactory;
        $this->itemFactory = $itemFactory;
        $this->trackCreationExtensionFactory = $trackCreationExtensionFactory;
    }

    /**
     * @inheritdoc
     */
    public function load()
    {
        $shipment = false;
        $orderId = $this->getOrderId();
        $shipmentId = $this->getShipmentId();
        if ($shipmentId) {
            try {
                $shipment = $this->shipmentRepository->get($shipmentId);
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage(__('This shipment no longer exists.'));
                return false;
            }
        } elseif ($orderId) {
            $order = $this->orderRepository->get($orderId);

            /**
             * Check order existing
             */
            if (!$order->getId()) {
                $this->messageManager->addErrorMessage(__('The order no longer exists.'));
                return false;
            }
            /**
             * Check shipment is available to create separate from invoice
             */
            if ($order->getForcedShipmentWithInvoice()) {
                $this->messageManager->addErrorMessage(__('Cannot do shipment for the order separately from invoice.'));
                return false;
            }
            /**
             * Check shipment create availability
             */
            if (!$order->canShip()) {
                $this->messageManager->addErrorMessage(__('Cannot do shipment for the order.'));
                return false;
            }

            $shipmentItems = $this->getShipmentItems($this->getShipment());

            $shipment = $this->documentFactory->create(
                $order,
                $shipmentItems,
                $this->getTrackingArray()
            );
        }

        $this->registry->register('current_shipment', $shipment);
        return $shipment;
    }

    /**
     * Convert UI-generated tracking array to Data Object array
     *
     * @return ShipmentTrackCreationInterface[]
     *
     * @throws LocalizedException
     */
    private function getTrackingArray(): array
    {
        $tracks = $this->getTracking() ?: [];
        $trackingCreation = [];
        foreach ($tracks as $track) {
            if (!isset($track['number']) || empty($track['title']) || !isset($track['carrier_code'])) {
                throw new LocalizedException(
                    __('Tracking information must contain carrier code and tracking number')
                );
            }

            $title = $track['title'];
            $lpName = $track['logistics_name'] ?? null;
            if ($title === '其他：自行填寫名稱') {
                $title = $lpName ?: $title;
            }
            $trackCreation = $this->trackFactory->create();
            $trackCreation->setTrackNumber($track['number']);
            $trackCreation->setTitle($title);
            $trackCreation->setCarrierCode($track['carrier_code']);

            // custom here
            if (!empty($track['logistics_company_url'])) {
                $extensionAttributes = $trackCreation->getExtensionAttributes();
                if (empty($extensionAttributes)) {
                    $extensionAttributes = $this->trackCreationExtensionFactory->create();
                }
                $extensionAttributes->setLogisticsCompanyUrl($track['logistics_company_url']);
                $trackCreation->setExtensionAttributes($extensionAttributes);
            }

            $trackingCreation[] = $trackCreation;
        }

        return $trackingCreation;
    }

    /**
     * Extract product id => product quantity array from shipment data.
     *
     * @param array $shipmentData
     *
     * @return array
     */
    private function getShipmentItems(array $shipmentData): array
    {
        $shipmentItems = [];
        $itemQty = $shipmentData['items'] ?? [];
        foreach ($itemQty as $itemId => $quantity) {
            $item = $this->itemFactory->create();
            $item->setOrderItemId($itemId);
            $item->setQty($quantity);
            $shipmentItems[] = $item;
        }
        return $shipmentItems;
    }
}
