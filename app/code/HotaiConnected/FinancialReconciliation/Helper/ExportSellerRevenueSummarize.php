<?php
declare(strict_types=1);

namespace HotaiConnected\FinancialReconciliation\Helper;

use Branch8\MarketPlaceOrderExport\Model\Services\GetTZOffsetTransitions;
use Branch8\MarketPlaceOrderExportSeller\Model\Columns\CommissionRate;
use Branch8\MarketPlaceOrderExportSeller\Model\Columns\NetSale;
use Branch8\MarketPlaceOrderExportSeller\Model\Columns\SalesRepresentatives;
use Branch8\MarketPlaceOrderExportSeller\Model\Columns\Summarize\NetTotal;
use Branch8\MarketPlaceOrderExportSeller\Model\Columns\Summarize\PlatformShare;
use Branch8\MarketPlaceOrderExportSeller\Model\Columns\Summarize\VendorShare;
use Magento\Framework\App\Area;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManager;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\LocalizedException;


/**
 * Class ExportSellerRevenueSummarize
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ExportSellerRevenueSummarize
{
    const  FROM_DATE = 'from';
    const  TO_DATE = 'to';

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
     * @param string $fromdate
     * @param string $todate
     * @param string|null $sellerCode
     * @return string
     * @throws LocalizedException
     * @throws \Exception
     */
    public function execute(string $fromdate, string $todate, ?string $sellerCode = null): string
    {
        if (is_null($fromdate) || is_null($todate)) {
            throw new LocalizedException(__('Please set From Date and To Date'));
        }
        $storeID = $this->storeManager->getStore()->getId();
        try {
            $this->appState->getAreaCode();
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_GLOBAL);
        }
        $this->emulation->startEnvironmentEmulation(1,
            Area::AREA_FRONTEND,
            true
        );
        $dateCombine = (new \DateTime($fromdate, new \DateTimeZone('UTC')))->format('YmdHis') . '_' . (new \DateTime($todate, new \DateTimeZone('UTC')))->format('YmdHis');
        $fileNameUniqueId = sprintf('seller-revenue-summarize-%s.xlsx', date('YmdHis'));
        $this->writter->setFileName($fileNameUniqueId);
        $this->writter->setRecordProvider($this->recordProvider);
        if (!$this->drawSummarySheet($fromdate, $todate, $sellerCode)) {
            $this->emulation->stopEnvironmentEmulation();
            return '';
        }
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
                'seller_settlement_invoices' => 'seller_settlement_invoices',
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
            __('廠商結帳發票'),
        ]);

        $connection = $this->resourceConnection->getConnection();

        // Fetch settlement invoices for all sellers in the period or specific seller
        $frTableName = $this->resourceConnection->getTableName('financial_reconciliation');
        $friTableName = $this->resourceConnection->getTableName('financial_reconciliation_invoice');

        $invoiceSelect = $connection->select()
            ->from(['fr' => $frTableName], ['seller_code', 'exclude_pending_gift_order'])
            ->joinLeft(
                ['fri' => $friTableName],
                'fr.id = fri.fr_id',
                ['invoices' => new \Zend_Db_Expr('GROUP_CONCAT(DISTINCT fri.invoice_number SEPARATOR ",")')]
            )
            ->where('fr.from = ?', $fromdate)
            ->where('fr.to = ?', $todate);

        if ($sellerCode && $sellerCode != 'all') {
            $invoiceSelect->where('fr.seller_code = ?', $sellerCode);
        }
        $invoiceSelect->group(['fr.seller_code', 'fr.exclude_pending_gift_order']);
        $sellerConfigs = $connection->fetchAll($invoiceSelect);

        $sellerInvoices = [];
        $excludeGiftSellers = [];
        foreach ($sellerConfigs as $config) {
            $sellerInvoices[$config['seller_code']] = $config['invoices'] ?? '';
            if ($config['exclude_pending_gift_order']) {
                $excludeGiftSellers[] = $config['seller_code'];
            }
        }

        // Log sellers that have exclude_pending_gift_order enabled
        if (!empty($excludeGiftSellers)) {
            $this->logger->info(sprintf(
                "Financial Reconciliation: Filtering pending gift orders for sellers: %s",
                implode(', ', $excludeGiftSellers)
            ));
        }

        $select = $this->getSellerSumarizeSql($excludeGiftSellers);
        $select->where('order_log.created_at >= ?', $fromdate)
            ->where('order_log.created_at <= ?', $todate);
        if ($sellerCode && $sellerCode != 'all') {
            $select->where('order_log.seller_code = ?', $sellerCode);
        }
        $records = $connection->fetchAll($select);
        if (empty($records)) {
            return false;
        }

        foreach ($records as &$record) {
            $record['seller_settlement_invoices'] = $sellerInvoices[$record['seller_code']] ?? '';
        }

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
            __('Transaction Price')->render()=>'',
            __('抽成%')->render() => '',
            __('Agreed service fee')->render() => '',
            __('Net Sales')->render() => '',
            __('Purchase Cost')->render() => '',
            __('Marketing Fee')->render() => '',
            __('Logistic Support Fee')->render() => '',
            __('Net Total')->render() => '',
            __('Sales Representatives')->render() => '',
            __('廠商結帳發票')->render() => '',
        ]);
        $this->writter->setTotalRecord($totalRow)->writeTotalRecord();
        return $this;
    }

    /**
     * @param array $excludeGiftSellers
     * @return \Magento\Framework\DB\Select
     */
    private function getSellerSumarizeSql($excludeGiftSellers = [])
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
            'logistic_support_fee' => new \Zend_Db_Expr("0"),//pharse 2
            'vendor_share' => new \Zend_Db_Expr(
                "ROUND(SUM(COALESCE(sales_order_item.seller_borne_total_amount, 0) * (CASE WHEN order_log.is_reverse = 1 THEN -1 ELSE 1 END)))"
            ),
        ];
        $select->from(
            ['order_log' => $this->resourceConnection->getTableName('ecpay_invoice_hotai_order_invoice_logs')],
        )->join(
            ['order_item_log' => $this->resourceConnection->getTableName('ecpay_invoice_hotai_order_item_invoice_logs')],
            'order_log.hotai_order_invoice_logs_id = order_item_log.hotai_order_invoice_log_id',
            []
        )->join(
            ['sales_order_item' => $this->resourceConnection->getTableName('sales_order_item')],
            'order_item_log.order_item_id=sales_order_item.item_id',
            []
        )->joinLeft(
            ['marketplace_saleperpartner' => $this->resourceConnection->getTableName('marketplace_saleperpartner')],
            'order_log.seller_id=marketplace_saleperpartner.seller_id'
        )->joinLeft(
            ['so' => $this->resourceConnection->getTableName('sales_order')],
            'order_log.order_id = so.entity_id',
            []
        )->where('order_log.seller_id IS NOT NULL')
            ->where('order_log.seller_code IS NOT NULL')
            ->where('order_item_log.type = ? ', 'item')
            ->group('order_log.seller_code')
            ->reset('columns')
            ->columns($columns);

        if (!empty($excludeGiftSellers)) {
            $select->where(
                'NOT (order_log.seller_code IN (?) AND so.is_gift_order = 1 AND so.is_gift_confirmed = 0)',
                $excludeGiftSellers
            );
        }

        return $select;
    }

    /**
     * @param $storeID
     * @return array
     */
    private function getDateAdd($storeID)
    {
        $timezone = $this->timezone->getConfigTimezone(
            \Magento\Store\Model\ScopeInterface::SCOPE_STORES, $storeID);
        $dateAdd = $this->getTZOffsetTransitions->get($timezone);
        return $dateAdd;
    }
}