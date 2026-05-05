<?php

declare(strict_types=1);

namespace Branch8\Sales\Rewrite\Model\Order;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\EntityManager\HydratorPool;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\ShipmentCommentCreationInterface;
use Magento\Sales\Api\Data\ShipmentCreationArgumentsInterface;
use Magento\Sales\Api\Data\ShipmentItemCreationInterface;
use Magento\Sales\Api\Data\ShipmentTrackCreationInterface;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Sales\Model\Order\ShipmentDocumentFactory\ExtensionAttributesProcessor;
use Magento\Sales\Model\Order\ShipmentFactory;

class ShipmentDocumentFactory extends \Magento\Sales\Model\Order\ShipmentDocumentFactory
{
    /**
     * @var ShipmentFactory
     */
    private ShipmentFactory $shipmentFactory;

    /**
     * @var TrackFactory
     */
    private TrackFactory $trackFactory;

    /**
     * @var HydratorPool
     */
    private HydratorPool $hydratorPool;

    /**
     * @var ExtensionAttributesProcessor
     */
    private ExtensionAttributesProcessor $extensionAttributesProcessor;

    /**
     * ShipmentDocumentFactory constructor.
     *
     * @param ShipmentFactory $shipmentFactory
     * @param HydratorPool $hydratorPool
     * @param TrackFactory $trackFactory
     * @param ExtensionAttributesProcessor|null $extensionAttributesProcessor
     */
    public function __construct(
        ShipmentFactory              $shipmentFactory,
        HydratorPool                 $hydratorPool,
        TrackFactory                 $trackFactory,
        ExtensionAttributesProcessor $extensionAttributesProcessor = null
    ) {
        parent::__construct(
            $shipmentFactory,
            $hydratorPool,
            $trackFactory,
            $extensionAttributesProcessor
        );
        $this->shipmentFactory = $shipmentFactory;
        $this->trackFactory = $trackFactory;
        $this->hydratorPool = $hydratorPool;
        $this->extensionAttributesProcessor = $extensionAttributesProcessor ?: ObjectManager::getInstance()
            ->get(ExtensionAttributesProcessor::class);
    }

    /**
     * @inheritdoc
     */
    public function create(
        OrderInterface                     $order,
        array                              $items = [],
        array                              $tracks = [],
        ShipmentCommentCreationInterface   $comment = null,
                                           $appendComment = false,
        array                              $packages = [],
        ShipmentCreationArgumentsInterface $arguments = null
    ) {
        $shipmentItems = empty($items)
            ? $this->getQuantitiesFromOrderItems($order->getItems())
            : $this->getQuantitiesFromShipmentItems($items);

        $shipment = $this->shipmentFactory->create($order, $shipmentItems);

        if (null !== $arguments) {
            $this->extensionAttributesProcessor->execute($shipment, $arguments);
        }

        foreach ($tracks as $track) {
            $hydrator = $this->hydratorPool->getHydrator(
                ShipmentTrackCreationInterface::class
            );

            // custom here
            $data = $hydrator->extract($track);
            $trackObj = $this->trackFactory->create()->addData($data);
            if (!empty($data['extension_attributes'])) {
                foreach ($data['extension_attributes'] as $attribute => $value) {
                    if (null === $value) {
                        continue;
                    }
                    $trackObj->setData($attribute, $value);
                }
            }
            $shipment->addTrack($trackObj);
        }

        if ($comment) {
            $shipment->addComment(
                $comment->getComment(),
                $appendComment,
                $comment->getIsVisibleOnFront()
            );

            if ($appendComment) {
                $shipment->setCustomerNote($comment->getComment());
                $shipment->setCustomerNoteNotify($appendComment);
            }
        }

        return $shipment;
    }

    /**
     * Translate OrderItemInterface array to product id => product quantity array.
     *
     * @param OrderItemInterface[] $items
     *
     * @return int[]
     */
    private function getQuantitiesFromOrderItems(array $items): array
    {
        $shipmentItems = [];
        foreach ($items as $item) {
            if (!$item->getIsVirtual() && (!$item->getParentItem() || $item->isShipSeparately())) {
                $shipmentItems[$item->getItemId()] = $item->getQtyOrdered();
            }
        }
        return $shipmentItems;
    }

    /**
     * Translate ShipmentItemCreationInterface array to product id => product quantity array.
     *
     * @param ShipmentItemCreationInterface[] $items
     *
     * @return int[]
     */
    private function getQuantitiesFromShipmentItems(array $items): array
    {
        $shipmentItems = [];
        foreach ($items as $item) {
            $shipmentItems[$item->getOrderItemId()] = $item->getQty();
        }
        return $shipmentItems;
    }
}
