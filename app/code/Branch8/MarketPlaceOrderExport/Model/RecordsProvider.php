<?php

namespace Branch8\MarketPlaceOrderExport\Model;

use Branch8\MarketPlaceOrderExport\Model\Services\GetEcpayInvoiceOrderLog;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManager\ObjectManager;
use Branch8\MarketPlaceOrderExport\Helper\Logger as LoggerInterface;

class RecordsProvider implements RecordsProviderInterface
{
    /**
     * @var array
     */
    protected array $columns;
    /**
     * @var ObjectManager
     */
    protected ObjectManager $objectManager;
    /**
     * @var array|null
     */
    protected $headers = null;
    /**
     * @var Source
     */
    protected Source $source;
    /**
     * @var Writer
     */
    protected $writer;

    protected array $columSettings;

    protected InvoiceGroupDataFactory $invoiceGroupDataFactory;
    protected GetEcpayInvoiceOrderLog $getEcpayInvoiceOrderLog;

    protected $allOrderItemRecords = [];

    protected $orderIds = [];

    protected LoggerInterface $logger;

    /**
     * @param ObjectManager $objectManager
     * @param Source $source
     * @param GetEcpayInvoiceOrderLog $getEcpayInvoiceOrderLog
     * @param InvoiceGroupDataFactory $invoiceGroupDataFactory
     * @param LoggerInterface $logger
     * @param array $columns
     * @param array $columSettings
     */
    public function __construct(
        Source                  $source,
        GetEcpayInvoiceOrderLog $getEcpayInvoiceOrderLog,
        InvoiceGroupDataFactory $invoiceGroupDataFactory,
        LoggerInterface         $logger,
        array                   $columns = [],
        array                   $columSettings = []
    )
    {
        $this->source = $source;
        $this->objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->columns = $columns;
        $this->invoiceGroupDataFactory = $invoiceGroupDataFactory;
        $this->columSettings = $columSettings;
        $this->getEcpayInvoiceOrderLog = $getEcpayInvoiceOrderLog;
        $this->logger = $logger;
    }

    /**
     * @return array
     */
    public function getColumnSettings()
    {
        return $this->columSettings;
    }

    /**
     * @param array $columns
     * @return $this|mixed
     */
    public function setColumns(array $columns)
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @param $header
     * @return $this|mixed
     */
    public function setHeader(array $header)
    {
        $this->headers = $header;
        return $this;
    }

    public function getHeader()
    {
        if ($this->headers === null) {
            $this->initHeader();
        }
        return $this->headers;
    }

    /**
     * @return array|null
     * @throws LocalizedException
     */
    protected function initHeader()
    {
        /**
         * @var $type ColumnInterface | string
         */
        foreach ($this->columns as $column => $type) {
            if (is_string($type)) {
                $this->headers[] = __($type)->render();
            } else if ($type instanceof ColumnInterface) {
                $this->headers[] = __($type->getHeader())->render();
            } else {
                throw new LocalizedException(__('Invalid column Interface'));
            }
        }
        return $this->headers;
    }

    /**
     * @param $orderIds
     * @return $this
     */
    public function setOrderIds($orderIds)
    {
        $this->orderIds = $orderIds;
        return $this;
    }

    /**
     * @param $orderId
     * @param array $ecpayIvoiceLogs
     * @return array
     * @throws LocalizedException
     */
    protected function getRecordsFromEcpayInvoiceLog($orderId, array $ecpayIvoiceLogs)
    {
        $records = [];
        $allRecords = $this->getAllRecordItems();
        $orderItems = $allRecords[$orderId];
        $lastItem = end($orderItems);
        foreach ($ecpayIvoiceLogs as $invoiceLogId => $logItems) {
            $key = 'invoice_' . $invoiceLogId;
            foreach ($logItems as $logItem) {
                if ($logItem['item_type'] === 'item') {
                    if (isset($allRecords[$orderId][$logItem['invoice_order_item_id']])) {
                        $itemRecord = array_merge($allRecords[$orderId][$logItem['invoice_order_item_id']], $logItem);
                        $records[$key]['records'][] = $itemRecord;
                    } else {
                        $this->logger->critical(__("ORDER + ORDER ITEM NOT MATCH %1-%2", $orderId, $logItem['invoice_order_item_id']));
                    }
                } else {
                    // clone last row of order then assign value it
                    $newRecord = $lastItem;
                    if ($logItem['item_type'] === 'shipping') {
                        $logItem['base_discount_amount'] = 0;
                        $logItem['vendor_share'] = 0;
                        $logItem['platform_share'] = 0;
                    }
                    $itemRecord = array_merge($newRecord, $logItem);
                    $records[$key]['records'][] = $itemRecord;
                }
            }
        }
        return $records;
    }

    /**
     * @param $orderIds
     * @return array|\Generator
     * @throws LocalizedException
     */
    protected function getAllRecordItems()
    {
        if ($this->allOrderItemRecords == null) {
            $this->allOrderItemRecords = [];
            $this->allOrderItemRecords = $this->source->getOrderItems($this->orderIds);
        }
        return $this->allOrderItemRecords;
    }

    /**
     * @param $records
     * @return $this
     */
    public function setAllRecordItems($records)
    {
        $this->allOrderItemRecords = $records;
        return $this;
    }

    /**
     * @param $orderIds
     * @return array
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAllRecordOrderItems($orderIds)
    {
        return $this->source->getOrderItems($orderIds);
    }

    /**
     * @return $this
     */
    protected function clearRecordsItems()
    {
        unset($this->allOrderItemRecords);
        $this->allOrderItemRecords = null;
        gc_collect_cycles();
        return $this;
    }

    /**
     * @param $orderIds
     * @return \Generator
     * @throws LocalizedException
     */
    public function getRecords($orderIds = [])
    {
        $allRecordRows = $this->setOrderIds($orderIds)
            ->clearRecordsItems()
            ->getAllRecordItems();
        $recordSet = [];
        $noInvoiceGroup = "no_invoice";
        foreach ($orderIds as $orderId) {
            $ecpayInvoiceLogs = $this->getEcpayInvoiceOrderLog->get($orderId);
            if ($ecpayInvoiceLogs) {
                $recordSet[$orderId] = $this->getRecordsFromEcpayInvoiceLog($orderId, $ecpayInvoiceLogs);
            } else {
                try {
                    $recordSet[$orderId][$noInvoiceGroup]['records'] = $allRecordRows[$orderId];
                } catch (\Exception $e) {
                    throw $e;
                }
            }
        }
        return $recordSet;
    }

    /**
     * @param $orderId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getEcpayInvoiceLog($orderId): array
    {
        return $this->getEcpayInvoiceOrderLog->get($orderId);
    }

    /**
     * @param Writer $writer
     * @return $this
     */
    public function setWriter(Writer $writer)
    {
        $this->writer = $writer;
        return $this;
    }
}
