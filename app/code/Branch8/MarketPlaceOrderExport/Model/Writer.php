<?php

namespace Branch8\MarketPlaceOrderExport\Model;

use Branch8\MarketPlaceOrderExport\Model\Services\GetShippingRowLog;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Sales\Model\OrderRepository;

class Writer extends AbstractWriter
{
    protected RecordsProviderFactory $recordProviderFactory;
    protected OrderRepository $orderRepository;
    private PerformanceAudit $audit;

    /**
     * @param Filesystem $filesystem
     * @param OrderRepository $orderRepository
     * @param RecordsProviderFactory $recordsProviderFactory
     * @param PerformanceAudit $audit
     */
    public function __construct(
        Filesystem                                             $filesystem,
        OrderRepository                                        $orderRepository,
        RecordsProviderFactory                                 $recordsProviderFactory,
        ScopeConfigInterface                                   $scopeConfig,
        \Branch8\MarketPlaceOrderExport\Model\PerformanceAudit $audit
    )
    {
        parent::__construct($filesystem, $scopeConfig);
        $this->audit = $audit;
        $this->recordProviderFactory = $recordsProviderFactory;
        $this->orderRepository = $orderRepository;
    }

    /***
     * @return $this|Writer
     * @throws LocalizedException
     */
    public function writeRecords()
    {
        $batches = array_chunk($this->ids, 1000);
        $this->audit->reset()->begin();
        $this->audit->setTotalOrders(count($this->ids));
        foreach ($batches as $key => $batch) {
            $this->audit->log("Batch:" . $key . ' - TotalItems:' . count($batch));
            $recordSet = $this->recordProvider->setOrderIds($batch)->getRecords($batch);
            foreach ($recordSet as $orderId => $invoiceGroups) {
                foreach ($invoiceGroups as $invoiceId => $invoiceGroup) {
                    foreach ($invoiceGroup['records'] as $row) {
                        $record = $this->buildRecord($row);
                        if ($row['item_type'] !== 'item') {
                            $this->setNulLColumn($record);
                        }
                        //$this->records[] = array_values($record);
                        $this->writeRecord(array_values($record));
                    }
                }
            }
            unset($recordSet);
            gc_collect_cycles();
        }
        $this->audit->end();
        return $this;
    }

    /**
     * @return $this|AbstractWriter
     */
    public function writeHeader()
    {
        $header = $this->getRecordProvider()->getHeader();
        $this->writeRecord($header, true);
        return $this;
    }


}
