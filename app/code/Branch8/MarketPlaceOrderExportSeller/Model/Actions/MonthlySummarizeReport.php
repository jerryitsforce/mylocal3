<?php

namespace Branch8\MarketPlaceOrderExportSeller\Model\Actions;

use Branch8\MarketPlaceOrderExport\Model\Services\GetTZOffsetTransitions;
use Branch8\MarketPlaceOrderExportSeller\Model\Columns\CommissionRate;
use Branch8\MarketPlaceOrderExportSeller\Model\Columns\NetSale;
use Branch8\MarketPlaceOrderExportSeller\Model\Columns\SalesRepresentatives;
use Branch8\MarketPlaceOrderExportSeller\Model\Columns\Summarize\NetTotal;
use Magento\Framework\App\Area;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MonthlySummarizeReport
{
    const  FROM_DATE = 'from_date';
    const  TO_DATE = 'to_date';

    const  SELLER_CODE = 'seller_code';
    /**
     * @var \Magento\Framework\File\Csv
     */
    private \Magento\Framework\File\Csv $csv;
    /**
     * @var \Magento\Framework\App\State
     */
    private \Magento\Framework\App\State $appState;

    private LoggerInterface $logger;
    private ResourceConnection $resourceConnection;

    private \Magento\Framework\Stdlib\DateTime\Timezone $timezone;
    private GetTZOffsetTransitions $getTZOffsetTransitions;
    private StoreManager $storeManager;
    /**
     * @var \Branch8\MarketPlaceOrderExportSeller\Model\SellerRevenueReportWriter
     */
    private $writter;
    /**
     * @var \Branch8\MarketPlaceOrderExportSeller\Model\SellerRevenueReportRecordProvider
     */
    private $recordProvider;
    /**
     * @var Emulation|mixed
     */
    private mixed $emulation;

    /**
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Framework\File\Csv $csv
     * @param ResourceConnection $resourceConnection
     * @param \Branch8\MarketPlaceOrderExportSeller\Model\SellerRevenueReportRecordProvider $recordProvider
     * @param \Branch8\MarketPlaceOrderExportSeller\Model\SellerRevenueReportWriter $writer
     * @param GetTZOffsetTransitions $getTZOffsetTransitions
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     * @param StoreManager $storeManager
     * @param LoggerInterface $logger
     * @param Emulation|null $emulation
     */
    public function __construct(
        \Magento\Framework\App\State                                                  $appState,
        \Magento\Framework\File\Csv                                                   $csv,
        ResourceConnection                                                            $resourceConnection,
        \Branch8\MarketPlaceOrderExportSeller\Model\SellerRevenueReportRecordProvider $recordProvider,
        \Branch8\MarketPlaceOrderExportSeller\Model\SellerRevenueReportWriter         $writer,
        GetTZOffsetTransitions                                                        $getTZOffsetTransitions,
        \Magento\Framework\Stdlib\DateTime\Timezone                                   $timezone,
        StoreManager                                                                  $storeManager,
        LoggerInterface                                                               $logger,
        Emulation                                                                     $emulation = null,
    )
    {
        $this->timezone = $timezone;
        $this->getTZOffsetTransitions = $getTZOffsetTransitions;
        $this->resourceConnection = $resourceConnection;
        $this->csv = $csv;
        $this->storeManager = $storeManager;
        $this->recordProvider = $recordProvider;
        $this->appState = $appState;
        $this->logger = $logger;
        $this->writter = $writer;
        $this->emulation = $emulation ?? ObjectManager::getInstance()->get(Emulation::class);
    }

    /**
     * @param $fromdate
     * @param $todate
     * @param $sellerCode
     * @return string
     * @throws \DateMalformedStringException
     * @throws \Magento\Framework\Exception\FileSystemException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute($fromdate, $todate, $sellerCode = '')
    {
        $this->emulation->startEnvironmentEmulation(1,
            Area::AREA_FRONTEND,
            true
        );
        $dateCombine = (new \DateTime($fromdate,
                new \DateTimeZone('UTC')))->format('YmdHis')
            . '_' . (new \DateTime($todate, new \DateTimeZone('UTC')))->format('YmdHis');
        $fileNameUniqueId = sprintf('seller-revenue-summarize-%s.xlsx', date('YmdHis'));
        $this->writter->setFileName($fileNameUniqueId);
        $this->writter->setRecordProvider($this->recordProvider);
        $this->drawSummarySheet($fromdate, $todate, $sellerCode);
        $filePath = $this->writter->save();
        $this->emulation->stopEnvironmentEmulation();
        return $filePath;
    }

    /**
     * @param $fromdate
     * @param $todate
     * @param $sellerCode
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function drawSummarySheet($fromdate, $todate, $sellerCode = null)
    {
        $this->writter->getSpreedSheet()->getActiveSheet()->setTitle(__('Summarize')->render());
        $this->recordProvider->setColumns(
            [
                'seller_code' => 'seller_code',
                'seller_company_name' => 'seller_company_name',
                'net_quantity' => 'net_quantity',
                'special_price' => 'special_price',
                'hotai_row_total' => 'hotai_row_total',
                'commision_rate' => ObjectManager::getInstance()->get(CommissionRate::class),
                'commission_amount' => 'commission_amount',
                'net_sale' => ObjectManager::getInstance()->get(NetSale::class),
                'purchase_cost' => 'purchase_cost',
                'vendor_share' => 'vendor_share',
                'logistic_support_fee' => 'logistic_support_fee',
                'net_total' => ObjectManager::getInstance()->get(NetTotal::class),
                'sales_representatives' => ObjectManager::getInstance()->get(SalesRepresentatives::class),
            ]
        );
        $this->recordProvider->setHeader([
            __('Seller Code'),
            __('Seller Company Name'),
            __('Number of Items'),
            __('Special Price'),
            __('Transaction Price'),
            __('抽成%'),
            __('Agreed service fee'),
            __('Net Sales'),
            __('Purchase Cost'),
            __('Marketing Fee'),
            __('Logistic Support Fee'),
            __('Net Total'),
            __('Sales Representatives'),
        ]);

        $connection = $this->resourceConnection->getConnection();
        $select = $this->getSellerSumarizeSql();
        $select->where('order_log.created_at >= ?', $fromdate)
            ->where('order_log.created_at <= ?', $todate);
        if ($sellerCode) {
            $select->where('order_log.seller_code = ?', $sellerCode);
        }
        $records = $connection->fetchAll($select);
        $this->writter->setTotalIndexColumns([
            __('Number of Items')->render() => 0,
            __('Special Price')->render() => 0,
            __('Transaction Price')->render() => 0,
            __('Agreed service fee')->render() => 0,
            __('Net Sales')->render() => 0,
            __('Purchase Cost')->render() => 0,
            __('Marketing Fee')->render() => 0,
            __('Logistic Support Fee')->render() => 0,
            __('Net Total')->render() => 0,
        ]);
        $this->writter->setRecords($records);
        $this->writter->writeHeader();
        $this->writter->writeRecords();

        $totalRow = ([
            __('Seller Code')->render() => '合計',
            __('Seller Company Name')->render() => '',
            __('Number of Items')->render() => '',
            __('Special Price')->render() => '',
            __('Transaction Price')->render() => '',
            __('抽成%')->render() => '',
            __('Agreed service fee')->render() => '',
            __('Net Sales')->render() => '',
            __('Purchase Cost')->render() => '',
            __('Marketing Fee')->render() => '',
            __('Logistic Support Fee')->render() => '',
            __('Net Total')->render() => '',
            __('Sales Representatives')->render() => '',
        ]);
        $this->writter->setTotalRecord($totalRow)->writeTotalRecord();
        return $this;
    }

    /**
     * @return \Magento\Framework\DB\Select
     */
    private function getSellerSumarizeSql()
    {
        $select = $this->resourceConnection->getConnection()->select();
        $commisionAmountExp = '
                ROUND(
        CASE
            WHEN marketplace_saleperpartner.min_commission_rate < 0
            THEN 0
            WHEN marketplace_saleperpartner.special_commission_rate IS NULL
            AND  marketplace_saleperpartner.min_commission_rate IS NULL
            THEN NULL
            WHEN (
                SUM(CASE WHEN order_log.is_reverse = 0 THEN order_item_log.include_tax ELSE 0 END) -
                SUM(CASE WHEN order_log.is_reverse = 1 THEN order_item_log.include_tax ELSE 0 END)
            ) IS NULL
            THEN NULL
            ELSE SUM(ROUND((
                (
                    (CASE WHEN order_log.is_reverse = 0 THEN ROUND(order_item_log.include_tax) * order_item_log.qty ELSE 0 END) -
                    (CASE WHEN order_log.is_reverse = 1 THEN ROUND(order_item_log.include_tax) * order_item_log.qty ELSE 0 END)
                ) * (CASE
                WHEN marketplace_saleperpartner.special_commission_rate IS NOT NULL
                THEN marketplace_saleperpartner.special_commission_rate
                ELSE marketplace_saleperpartner.min_commission_rate
            END) / 100))
            )
        END
    )
        ';


        $columns = [
            'seller_id' => 'seller_id',
            'order_item_ids' => new \Zend_Db_Expr('GROUP_CONCAT(order_item_log.order_item_id)'),
            'seller_code' => 'order_log.seller_code',
            'seller_company_name' => 'order_log.seller_shop_name',
            'net_quantity' => new \Zend_Db_Expr('(SUM(CASE WHEN order_log.is_reverse = 0 THEN order_item_log.qty ELSE 0 END) - SUM(CASE WHEN order_log.is_reverse = 1 THEN order_item_log.qty ELSE 0 END))'),
            'special_price' => new \Zend_Db_Expr(
                'SUM((CASE WHEN order_log.is_reverse = 1 THEN -ROUND(sales_order_item.special_price)
                ELSE ROUND(sales_order_item.special_price)
                END
                ) * sales_order_item.qty_ordered)'
            ),
            'hotai_row_total' => new \Zend_Db_Expr('
             ROUND((SUM(CASE WHEN order_log.is_reverse = 0 THEN (order_item_log.include_tax) * order_item_log.qty ELSE 0 END) - SUM(CASE WHEN order_log.is_reverse = 1 THEN order_item_log.include_tax * order_item_log.qty ELSE 0 END)))'),
            'commision_rate' => new \Zend_Db_Expr('CASE WHEN marketplace_saleperpartner.special_commission_rate IS NOT NULL THEN marketplace_saleperpartner.special_commission_rate ELSE marketplace_saleperpartner.min_commission_rate END'),
            'commission_amount' => new \Zend_Db_Expr($commisionAmountExp),
            'purchase_cost' => new \Zend_Db_Expr(
                'ROUND(SUM(CASE WHEN order_log.is_reverse = 1 THEN -ROUND(COALESCE(sales_order_item.base_cost * order_item_log.qty, 0))
                           ELSE ROUND(COALESCE(sales_order_item.base_cost * order_item_log.qty, 0))
                           END))'
            ),
            'marketing_fee' => new \Zend_Db_Expr("0"),// pharse 2
            'logistic_support_fee' => new \Zend_Db_Expr("
                        ROUND(SUM(CASE WHEN sales_order.status IN ('arrived', 'complete')
                                       THEN  sales_order_item.seller_shipping_amount
                                       ELSE 0
                                       END
                            ))
            "),//pharse 2
            'vendor_share' => new \Zend_Db_Expr(
                "ROUND(SUM(COALESCE(sales_order_item.seller_borne_total_amount, 0) * (CASE WHEN order_log.is_reverse = 1 THEN -1 ELSE 1 END)))"
            ),
        ];
        $select->from(
            'ecpay_invoice_hotai_order_invoice_logs as order_log',
        )->join(
            'ecpay_invoice_hotai_order_item_invoice_logs as order_item_log',
            'order_log.hotai_order_invoice_logs_id = order_item_log.hotai_order_invoice_log_id',
            []
        )->join('sales_order_item',
            'order_item_log.order_item_id=sales_order_item.item_id',
            []
        )->join('sales_order',
            'sales_order_item.order_id=sales_order.entity_id',
            []
        )->joinLeft(
            'marketplace_saleperpartner',
            'order_log.seller_id=marketplace_saleperpartner.seller_id'
        )->where('order_log.seller_id IS NOT NULL')
            ->where('order_log.seller_code IS NOT NULL')
            ->where('order_item_log.type = ? ', 'item')
            ->group('order_log.seller_code')
            //->order('order_log.seller_code')
            ->reset('columns')
            ->columns($columns);
        return $select;
    }
}
