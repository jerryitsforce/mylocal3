<?php

namespace Branch8\MarketPlaceOrderExport\Model;

use Branch8\MarketPlaceOrderExport\Model\Services\GetEcpayInvoiceOrderItemLogRecord;
use Branch8\MarketPlaceOrderExport\Model\Services\GetEcpayInvoiceOrderLog;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Sales\Model\OrderRepository;

class Writer2 extends AbstractWriter
{
    protected RecordsProviderFactory $recordProviderFactory;
    protected OrderRepository $orderRepository;
    private PerformanceAudit $audit;
    private GetEcpayInvoiceOrderItemLogRecord $getEcpayInvoiceOrderItemLogRecord;

    /**
     * @param Filesystem $filesystem
     * @param OrderRepository $orderRepository
     * @param RecordsProviderFactory $recordsProviderFactory
     * @param GetEcpayInvoiceOrderItemLogRecord $getEcpayInvoiceOrderItemLogRecord
     * @param ScopeConfigInterface $scopeConfig
     * @param PerformanceAudit $audit
     */
    public function __construct(
        Filesystem                                             $filesystem,
        OrderRepository                                        $orderRepository,
        RecordsProviderFactory                                 $recordsProviderFactory,
        GetEcpayInvoiceOrderItemLogRecord                      $getEcpayInvoiceOrderItemLogRecord,
        ScopeConfigInterface                                   $scopeConfig,
        \Branch8\MarketPlaceOrderExport\Model\PerformanceAudit $audit
    )
    {
        parent::__construct($filesystem, $scopeConfig);
        $this->recordProviderFactory = $recordsProviderFactory;
        $this->orderRepository = $orderRepository;
        $this->audit = $audit;
        $this->getEcpayInvoiceOrderItemLogRecord = $getEcpayInvoiceOrderItemLogRecord;
    }

    /**
     * @param $records
     * @return void
     * @throws LocalizedException
     */
    private function writeNormalRecords($batch)
    {
        foreach ($batch as $orderIds => $records) {
            foreach ($records as $row) {
                $this->writeRecord(array_values($this->buildRecord($row)));
            }
        }
    }

    private function writeEcpayLogRecords($records)
    {
        foreach ($records as $row) {
            $this->writeRecord(array_values($this->buildRecord($row)));
        }
    }

    /***
     * @return $this|Writer
     * @throws LocalizedException
     */
    public function writeRecords()
    {
        // $this->ids = [1672188];
        $batches = array_chunk($this->ids, 10000);
        $this->audit->reset()->begin();
        $this->audit->setTotalOrders(count($this->ids));
        foreach ($batches as $key => $batch) {
            $this->audit->log("Batch:" . $key . ' - TotalItems:' . count($batch));
            foreach ($batch as $orderId) {
                $ecpayLogRecords = $this->getEcpayInvoiceOrderItemLogRecord->get($orderId);
                if ($ecpayLogRecords) {
                    $this->writeEcpayLogRecords($ecpayLogRecords);
                } else {
                    $this->writeNormalRecords($this->recordProvider->getAllRecordOrderItems([$orderId]));
                }
            }
            unset($ecpayLogRecords);
            gc_collect_cycles();
        }
        $this->audit->end();
        if ($this->getIstream()) {
            $this->save(true);
            exit;
        }
        return $this;
    }

    public function writeHeader()
    {
        $header = $this->getRecordProvider()->getHeader();
        $this->writeRecord($header, true);
        return $this;
    }
}
