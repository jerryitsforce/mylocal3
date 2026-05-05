<?php
namespace Branch8\ShipmentImport\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\ImportExport\Helper\Data;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\Import;
use Magento\Framework\EntityManager\HydratorPool;
use Magento\ImportExport\Model\ResourceModel\Helper;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Convert\Order as ConvertOrder;
use Magento\Sales\Model\Order\Email\Sender\ShipmentSender;
use Magento\Sales\Model\Order\Shipment\TrackFactory;
use Magento\Shipping\Model\Config;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Stdlib\StringUtils;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Magento\Sales\Api\Data\ShipmentTrackCreationInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory as ShipmentCollectionFactory;
use Magento\Framework\DB\Transaction;

/**
 * Class ImportShipment
 * @package Branch8\ShipmentImport\Model
 */
class ImportShipment extends AbstractEntity
{
    /**
     * @var ObjectManagerInterface
     */
    protected ObjectManagerInterface $objectManager;

    /**
     * @var OrderInterface
     */
    protected OrderInterface $orderModel;

    /**
     * @var ConvertOrder
     */
    protected ConvertOrder $convertOrder;

    /**
     * @var ShipmentSender
     */
    protected ShipmentSender $shipmentSender;

    /**
     * @var Config
     */
    protected Config $shippingConfig;

    /**
     * @var TrackFactory
     */
    private TrackFactory $trackFactory;

    /**
     * @var ShipmentRepositoryInterface
     */
    private ShipmentRepositoryInterface $shipmentRepository;

    /**
     * @var ShipmentCollectionFactory
     */
    private ShipmentCollectionFactory $shipmentCollectionFactory;

    /**
     * @var HydratorPool
     */
    private HydratorPool $hydratorPool;

    /**
     * @var array
     */
    protected array $carriers = [];

    /**
     * Coupon constructor.
     * @param JsonHelper $jsonHelper
     * @param Data $importExportData
     * @param \Magento\ImportExport\Model\ResourceModel\Import\Data $importData
     * @param EavConfig $config
     * @param ResourceConnection $resource
     * @param Helper $resourceHelper
     * @param StringUtils $string
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param ObjectManagerInterface $objectManager
     * @param OrderInterface $order
     * @param ConvertOrder $convertOrder
     * @param ShipmentSender $shipmentSender
     * @param Config $shippingConfig
     * @param HydratorPool $hydratorPool
     * @param TrackFactory $trackFactory
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param ShipmentCollectionFactory $shipmentCollectionFactory
     */
    public function __construct(
        JsonHelper $jsonHelper,
        Data $importExportData,
        \Magento\ImportExport\Model\ResourceModel\Import\Data $importData,
        EavConfig $config,
        ResourceConnection $resource,
        Helper $resourceHelper,
        StringUtils $string,
        ProcessingErrorAggregatorInterface $errorAggregator,
        ObjectManagerInterface $objectManager,
        OrderInterface $order,
        ConvertOrder $convertOrder,
        ShipmentSender $shipmentSender,
        Config $shippingConfig,
        HydratorPool $hydratorPool,
        TrackFactory $trackFactory,
        ShipmentRepositoryInterface $shipmentRepository,
        ShipmentCollectionFactory $shipmentCollectionFactory
    ) {
        $this->jsonHelper = $jsonHelper;
        $this->_importExportData = $importExportData;
        $this->_resourceHelper = $resourceHelper;
        $this->_dataSourceModel = $importData;
        $this->errorAggregator = $errorAggregator;
        $this->objectManager = $objectManager;
        $this->orderModel = $order;
        $this->convertOrder = $convertOrder;
        $this->shipmentSender = $shipmentSender;
        $this->shippingConfig = $shippingConfig;
        $this->trackFactory = $trackFactory;
        $this->hydratorPool = $hydratorPool;
        $this->shipmentRepository = $shipmentRepository;
        $this->shipmentCollectionFactory = $shipmentCollectionFactory;

        foreach ($this->errorMessageTemplates as $errorCode => $message) {
            $this->getErrorAggregator()->addErrorMessageTemplate($errorCode, $message);
        }

        $this->initCarriers();
        $this->initMessageTemplates();
    }

    /**
     * Entity type code getter.
     *
     * @return string
     */
    public function getEntityTypeCode(): string
    {
        return 'import_shipment';
    }

    /**
     * Row validation
     *
     * @param array $rowData
     * @param int $rowNum
     * @return bool
     */
    public function validateRow(array $rowData, $rowNum): bool
    {
        $rowData = array_map('trim', $rowData);

        $order = $rowData['sub-order'] ?? '';
        $courier = $rowData['courier'] ?? '';
        $trackingNumber = $rowData['tracking_number'] ?? '';

        if (!$order) {
            $this->addRowError('SubOrderIsRequired', $rowNum);
        }

        if (!$courier) {
            $this->addRowError('CourierIsRequired', $rowNum);
        }

        if (!$trackingNumber) {
            $this->addRowError('TrackingNumberIsRequired', $rowNum);
        }

        if (isset($this->_validatedRows[$rowNum])) {
            return !$this->getErrorAggregator()->isRowInvalid($rowNum);
        }

        $this->_validatedRows[$rowNum] = true;

        return !$this->getErrorAggregator()->isRowInvalid($rowNum);

    }

    /**
     * Init Error Messages
     */
    private function initMessageTemplates(): void
    {
        $this->addMessageTemplate(
            'SubOrderIsRequired',
            __('Sub-Order Increment ID cannot be empty.')
        );
        $this->addMessageTemplate(
            'CourierIsRequired',
            __('Courier cannot be empty.')
        );
        $this->addMessageTemplate(
            'CourierIsNotCorrect',
            __('Courier is not correct.')
        );
        $this->addMessageTemplate(
            'TrackingNumberIsRequired',
            __('Tracking Number cannot be empty.')
        );
    }

    /**
     * Import data
     */
    protected function _importData()
    {
        if (Import::BEHAVIOR_APPEND == $this->getBehavior()) {
            $this->saveEntity();
        }
        return true;
    }

    /**
     * Save entity
     */
    public function saveEntity()
    {
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            foreach ($bunch as $rowNum => $rowData) {
                if (!$this->validateRow($rowData, $rowNum)) {
                    continue;
                }
                $order = $this->orderModel->loadByIncrementId($rowData['sub-order']);
                if (!$order->getId()) {
                    $this->addRowError(__('Order with increment ID %1 not found.', $rowData['sub-order']), $this->_processedRowsCount);
                    $this->_processedRowsCount++;
                    continue;
                }
                $carrier = $this->carriers[strtolower(trim($rowData['courier']))] ?? 'custom';
                $trackCreation = $this->trackFactory->create();
                $trackCreation->setTrackNumber($rowData['tracking_number']);
                $trackCreation->setTitle($rowData['courier']);
                $trackCreation->setCarrierCode($carrier);
                $hydrator = $this->hydratorPool->getHydrator(
                    ShipmentTrackCreationInterface::class
                );
                // to check order already have shipment or not
                if ($order->hasShipments()) {
                    $shipment = $this->shipmentCollectionFactory->create()->addFieldToFilter('order_id', $order->getId())->getFirstItem();
                    if ($shipment) {
                        $shipment->addTrack($trackCreation);
                        $this->shipmentRepository->save($shipment);
                    }
                    $this->_processedRowsCount++;
                    $this->countItemsCreated++;
                    continue;
                }
                // to check order can ship or not
                if (!$order->canShip()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                    $this->addRowError(__('You can\'t create an shipment for this sub-order: %1.', $rowData['sub-order']), $this->_processedRowsCount);
                    $this->_processedRowsCount++;
                    continue;
                }
                $orderShipment = $this->convertOrder->toShipment($order);
                foreach ($order->getAllItems() AS $orderItem) {
                    // Check virtual item and item Quantity
                    if (!$orderItem->getQtyToShip() || $orderItem->getIsVirtual()) {
                        continue;
                    }
                    $qty = $orderItem->getQtyToShip();
                    $shipmentItem = $this->convertOrder->itemToShipmentItem($orderItem)->setQty($qty);
                    $orderShipment->addItem($shipmentItem);
                }
                $orderShipment->addTrack($this->trackFactory->create(['data' => $hydrator->extract($trackCreation)]));
                $orderShipment->register();
                $orderShipment->getOrder()->setCustomerNoteNotify(0);
                try {
                    $orderShipment->getOrder()->setIsInProcess(true);
                    // Save created Order Shipment
                    $transaction = $this->objectManager->create(
                        Transaction::class
                    );
                    $transaction->addObject(
                        $orderShipment
                    )->addObject(
                        $orderShipment->getOrder()
                    )->save();

                    // Send Shipment Email
                    $this->shipmentSender->send($orderShipment);
                } catch (\Exception $e) {
                    if ($e->getMessage() == __('Not all of your products are available in the requested quantity.')) {
                        $listSku = [];
                        foreach ($orderShipment->getItems() as $item) {
                            $itemSku = $item->getSku();
                            $listSku[] = $itemSku;
                        }
                        $this->addRowError(__('Not all of your products are available in the requested quantity: %1', implode(',', $listSku)), $this->_processedRowsCount);
                    } else {
                        $this->addRowError($e->getMessage(), $this->_processedRowsCount);
                    }
                }
                $this->_processedRowsCount++;
                $this->countItemsCreated++;
            }
        }

        return $this;
    }

    public function validateData(): ProcessingErrorAggregatorInterface
    {
        if (!$this->_dataValidated) {
            $this->getErrorAggregator()->clear();
            $this->_saveValidatedBunches();
            $this->_dataValidated = true;
        }
        return $this->getErrorAggregator();
    }

    protected function _saveValidatedBunches()
    {
        $source = $this->_getSource();
        $currentDataSize = 0;
        $bunchRows = [];
        $startNewBunch = false;
        $nextRowBackup = [];
        $maxDataSize = $this->_resourceHelper->getMaxDataSize();
        $bunchSize = $this->_importExportData->getBunchSize();
        $source->rewind();
        $this->_dataSourceModel->cleanBunches();

        while ($source->valid() || $bunchRows) {
            if ($startNewBunch || !$source->valid()) {
                $this->_dataSourceModel->saveBunch($this->getEntityTypeCode(), $this->getBehavior(), $bunchRows);

                $bunchRows = $nextRowBackup;
                $currentDataSize = strlen(serialize($bunchRows));
                $startNewBunch = false;
                $nextRowBackup = [];
            }
            if ($source->valid()) {
                try {
                    $rowData = $source->current();
                } catch (\InvalidArgumentException $e) {
                    $this->addRowError($e->getMessage(), $this->_processedRowsCount);
                    $this->_processedRowsCount++;
                    $source->next();
                    continue;
                }
                $this->_processedRowsCount++;
                if ($this->validateRow($rowData, $source->key())) {
                    // add row to bunch for save
                    $rowData = $this->_prepareRowForDb($rowData);
                    $rowSize = strlen($this->jsonHelper->jsonEncode($rowData));
                    $isBunchSizeExceeded = $bunchSize > 0 && count($bunchRows) >= $bunchSize;
                    if ($currentDataSize + $rowSize >= $maxDataSize || $isBunchSizeExceeded) {
                        $startNewBunch = true;
                        $nextRowBackup = [$source->key() => $rowData];
                    } else {
                        $bunchRows[$source->key()] = $rowData;
                        $currentDataSize += $rowSize;
                    }
                }
                $source->next();
            }
        }
        return $this;
    }

    /**
     *
     * Multiple value separator getter.
     * @return string
     */
    public function getMultipleValueSeparator(): string
    {
        if (!empty($this->_parameters[Import::FIELD_FIELD_MULTIPLE_VALUE_SEPARATOR])) {
            return $this->_parameters[Import::FIELD_FIELD_MULTIPLE_VALUE_SEPARATOR];
        }
        return Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR;
    }

    /**
     * Retrieve carriers
     *
     * @return array
     */
    public function initCarriers(): array
    {
        if (empty($this->carriers)) {
            $carriers = [];
            $carrierInstances = $this->_getCarriersInstances();
            foreach ($carrierInstances as $code => $carrier) {
                if ($carrier->isTrackingAvailable()) {
                    $carriers[strtolower($carrier->getConfigData('title'))] = $code;
                }
            }
            $this->carriers = $carriers;
        }
        return $this->carriers;
    }

    /**
     * @return array
     */
    protected function _getCarriersInstances(): array
    {
        return $this->shippingConfig->getAllCarriers(0);
    }
}
