<?php

namespace HotaiConnected\FinancialReconciliation\Model;

use HotaiConnected\FinancialReconciliation\Api\MarketPlaceReconciliationManagementInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Webapi\Rest\Response;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Customer\Model\Session;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\Controller\ResultFactory;
use Psr\Log\LoggerInterface; // 新增 LoggerInterface
use HotaiConnected\FinancialReconciliation\Helper\EcpaySellerRevenueExporter; // 新增 EcpaySellerRevenueExporter
use Magento\Framework\Filesystem; // 新增 Filesystem

class MarketPlaceReconciliationManagement implements MarketPlaceReconciliationManagementInterface
{
    const SETTLEMENT_STATUS = [
        1 => '建立',
        2 => '解除',
        3 => '寄出對帳單'
    ];

    protected $resourceConnection;
    protected $response;
    protected $jsonSerializer;
    protected $customerSession;
    protected $resultFactory;
    protected $logger; // 新增 logger 屬性
    protected $ecpaySellerRevenueExporter; // 新增 ecpaySellerRevenueExporter 屬性
    protected $fileSystem; // 新增 fileSystem 屬性

    public function __construct(
        ResourceConnection $resourceConnection,
        Response $response,
        Json $jsonSerializer,
        Session $customerSession,
        ResultFactory $resultFactory,
        LoggerInterface $logger, // 注入 LoggerInterface
        EcpaySellerRevenueExporter $ecpaySellerRevenueExporter, // 注入 EcpaySellerRevenueExporter
        Filesystem $fileSystem // 注入 Filesystem
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->response = $response;
        $this->jsonSerializer = $jsonSerializer;
        $this->customerSession = $customerSession;
        $this->resultFactory = $resultFactory;
        $this->logger = $logger; // 賦值給屬性
        $this->ecpaySellerRevenueExporter = $ecpaySellerRevenueExporter; // 賦值給屬性
        $this->fileSystem = $fileSystem; // 賦值給屬性
    }

    /**
     * @inheritDoc
     */
    public function getCustomerReconciliation(?string $from = null, ?string $to = null, int $currentPage = 1, int $pageSize = 20)
    {
        // 確保用戶已登入
        $customerId = $this->customerSession->getCustomerId();

        if (!$customerId) {
            $response = ['message' => '您必須登入才能取得賣家資訊。'];
            return $this->sendJsonResponse($response);
        }

        $this->logger->info(__FUNCTION__ . ' customer id: ' . $customerId);
        $connection = $this->resourceConnection->getConnection();
        $tableName  = $this->resourceConnection->getTableName('financial_reconciliation');
        $marketplaceUserdataTableName = $this->resourceConnection->getTableName('marketplace_userdata');

        // 獲取 seller_code (支援主帳號與子帳號)
        $sellerCode = $this->getSellerCodeByCustomerId($customerId);
        if (!$sellerCode) {
            $response = ['message' => '找不到對應的賣家資訊。'];
            return $this->sendJsonResponse($response);
        }

        $select = $connection->select()
                ->from(['fr' => $tableName])
                ->joinLeft(
                    ['mu' => $marketplaceUserdataTableName],
                    'fr.seller_code = mu.seller_code',
                    ['shop_title' => 'mu.shop_title', 'salesperson']
                )
                ->joinLeft(
                    ['ar' => $this->resourceConnection->getTableName('authorization_role')],
                    'ar.role_id = mu.salesperson',
                    ['salesperson_role_name' => 'ar.role_name']
                )
                ->joinLeft(
                    ['ms' => $this->resourceConnection->getTableName('marketplace_saleperpartner')],
                    'mu.seller_id = ms.seller_id',
                    ['commission_rate' => 'ms.commission_rate']
                )
                ->where('fr.settlement_status = ?', array_search('寄出對帳單', self::SETTLEMENT_STATUS));

        if (!empty($sellerCode)) {
            $select->where('fr.seller_code = ?', $sellerCode);
        }
        if ($from) {
            $select->where('fr.created_at >= ?', $from);
        }
        if ($to) {
            $select->where('fr.created_at <= ?', $to);
        }

        // 計算總記錄數
        $totalRecordsSelect = clone $select;
        $totalRecordsSelect->reset(\Magento\Framework\DB\Select::COLUMNS);
        $totalRecordsSelect->columns(new \Zend_Db_Expr('COUNT(*)'));
        $totalRecords = (int)$connection->fetchOne($totalRecordsSelect);

        // 應用分頁
        $offset = ($currentPage - 1) * $pageSize;
        $select->limit($pageSize, $offset);

        $result = $connection->fetchAll($select);
        foreach ($result as &$item) {
            if (isset($item['created_at'])) {
                $item['created_at'] = (new \DateTime($item['created_at']))->setTimezone(new \DateTimeZone('Asia/Taipei'))->format('Y-m-d H:i:s');
            }
            if (isset($item['updated_at'])) {
                $item['updated_at'] = (new \DateTime($item['updated_at']))->setTimezone(new \DateTimeZone('Asia/Taipei'))->format('Y-m-d H:i:s');
            }

            // Calculate total_paid
            $sellerCode = $item['seller_code'];
            $fromDate = $item['from'];
            $toDate = $item['to'];

            $totalPaidSelect = $connection->select()
                ->from(
                    ['order_log' => $this->resourceConnection->getTableName('ecpay_invoice_hotai_order_invoice_logs')],
                    [
                        'total_paid' => new Expression('
                            ROUND(
                                SUM(
                                    CASE WHEN order_log.is_reverse = 1 THEN -1 ELSE 1 END *
                                    (
                                        (COALESCE(sales_order_item.base_cost * order_item_log.qty, 0))
                                        - COALESCE(sales_order_item.seller_borne_total_amount, 0)
                                        + 0
                                    )
                                )
                            )
                        '),
                        'special_price' => new Expression('
                            SUM(
                                CASE
                                    WHEN sales_order_item.variation_price > 0
                                        THEN (CASE WHEN order_log.is_reverse = 1 THEN -ROUND(sales_order_item.variation_price) ELSE ROUND(sales_order_item.variation_price) END)
                                    ELSE (CASE WHEN order_log.is_reverse = 1 THEN -ROUND(sales_order_item.special_price) ELSE ROUND(sales_order_item.special_price) END)
                                END
                                * sales_order_item.qty_ordered
                            )
                        ')
                    ]
                )
                ->join(
                    ['order_item_log' => $this->resourceConnection->getTableName('ecpay_invoice_hotai_order_item_invoice_logs')],
                    'order_log.hotai_order_invoice_logs_id = order_item_log.hotai_order_invoice_log_id',
                    []
                )
                ->join(
                    ['sales_order_item' => $this->resourceConnection->getTableName('sales_order_item')],
                    'order_item_log.order_item_id = sales_order_item.item_id',
                    []
                )
                ->where('sales_order_item.seller_code = ?', $sellerCode)
                ->where('order_log.created_at >= ?', $fromDate)
                ->where('order_log.created_at <= ?', $toDate)
                ->where('order_item_log.type = ?', 'item');

            $calculatedData = $connection->fetchRow($totalPaidSelect);

            $item['price'] = ($calculatedData && isset($calculatedData['special_price'])) ? (float)$calculatedData['special_price'] : 0;
            $item['total_paid'] = ($calculatedData && isset($calculatedData['total_paid'])) ? (float)$calculatedData['total_paid'] : 0;
            if (isset($item['settlement_status']) && isset(self::SETTLEMENT_STATUS[(int)$item['settlement_status']])) {
                $item['settlement_status'] = self::SETTLEMENT_STATUS[(int)$item['settlement_status']];
            }
        }
        $response = [
            'items' => $result,
            'total_record' => $totalRecords,
            'pagination' => $currentPage,
            'page_size' => $pageSize
        ];

        // Magento 2 的 Web API 通常期望返回資料陣列，由框架自動處理序列化。
        // 但根據您的要求，我將直接設定並發送響應。
        return $this->sendJsonResponse($response);
    }

    /**
     * @inheritDoc
     */
    public function downloadCustomerEcpaySellerRevenueDetail(int $id)
    {
        $connection = $this->resourceConnection->getConnection();
        $financialReconciliationTableName = $this->resourceConnection->getTableName('financial_reconciliation');
        $marketplaceUserdataTableName = $this->resourceConnection->getTableName('marketplace_userdata');

        try {
            // 1. 登入者驗證
            $customerId = $this->customerSession->getCustomerId();
            if (!$customerId) {
                $response = ['success' => false, 'message' => '您必須登入才能下載對帳單。'];
                return $this->sendJsonResponse($response);
            }

            // 取得登入者的 seller_code (支援主帳號與子帳號)
            $loggedInSellerCode = $this->getSellerCodeByCustomerId($customerId);

            if (!$loggedInSellerCode) {
                $response = ['success' => false, 'message' => '找不到對應的賣家資訊。'];
                return $this->sendJsonResponse($response);
            }

            // 2. 驗證 ID 和 seller_code
            $selectRecord = $connection->select()
                ->from(['fr' => $financialReconciliationTableName], ['id', 'from', 'to', 'seller_code'])
                ->where('fr.id = ?', $id);
            $reconciliationRecord = $connection->fetchRow($selectRecord);

            if (empty($reconciliationRecord)) {
                $response = ['success' => false, 'message' => '找不到指定的對帳單記錄 (ID: ' . $id . ')。'];
                return $this->sendJsonResponse($response);
            }

            if ($reconciliationRecord['seller_code'] !== $loggedInSellerCode) {
                $response = ['success' => false, 'message' => '您沒有權限下載此對帳單 (ID: ' . $id . ')。'];
                return $this->sendJsonResponse($response);
            }

            $directoryWrite = $this->fileSystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::TMP);
            $tempRelativePath = 'marketplace_seller_revenue_detail';
            $tempDir = $directoryWrite->getAbsolutePath($tempRelativePath);

            if (!$directoryWrite->isExist($tempRelativePath)) {
                $directoryWrite->create($tempRelativePath);
            }

            // exportAndZip now returns the path to a single Excel file
            $excelFilePath = $this->ecpaySellerRevenueExporter->exportAndZip(
                $reconciliationRecord['from'],
                $reconciliationRecord['to'],
                $reconciliationRecord['seller_code']
            );

            if (!file_exists($excelFilePath)) {
                $this->deleteDirectory($tempRelativePath); // Clean up temp directory if no file was generated
                $response = ['success' => false, 'message' => '沒有生成任何導出文件。'];
                return $this->sendJsonResponse($response);
            }

            $fileName = basename($excelFilePath);
            $sellerCode = $reconciliationRecord['seller_code'];
            $dateStr = (new \DateTime('now', new \DateTimeZone('Asia/Taipei')))->format('Ymd');
            $asciiFileName = 'reconciliation-' . $sellerCode . '_' . $dateStr . '.xlsx';

            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename*=UTF-8\'\'' . rawurlencode($fileName) . '; filename="' . $asciiFileName . '"');

            ob_clean();
            flush();

            readfile($excelFilePath);

            unlink($excelFilePath);
            $this->deleteDirectory($tempRelativePath);
            exit;

        } catch (\Exception $e) {
            $this->logger->error('Marketplace Seller Revenue Detail Download API Error: ' . $e->getMessage());
            $response = ['success' => false, 'message' => '發生意外錯誤: ' . $e->getMessage()];
            return $this->sendJsonResponse($response);
        }
    }

    /**
     * Send JSON response with HSTS header.
     *
     * @param array $responseData
     * @return \Magento\Framework\Webapi\Rest\Response
     */
    private function sendJsonResponse(array $responseData)
    {
        return $this->response->setHeader('Content-Type', 'application/json', true)
            ->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains', true)
            ->setBody($this->jsonSerializer->serialize($responseData))
            ->sendResponse();
    }

    /**
     * Get seller code by customer ID (supports main account and sub-account)
     *
     * @param int|string $customerId
     * @return string|bool
     */
    private function getSellerCodeByCustomerId($customerId)
    {
        $connection = $this->resourceConnection->getConnection();
        $marketplaceUserdataTableName = $this->resourceConnection->getTableName('marketplace_userdata');

        // 1. 查詢主帳號 marketplace_userdata 以獲取 seller_code
        $sellerCodeSelect = $connection->select()
            ->from(['mu' => $marketplaceUserdataTableName], ['seller_code'])
            ->where('mu.seller_id = ?', $customerId);
        $sellerCode = $connection->fetchOne($sellerCodeSelect);

        // 2. 若主帳號查不到 seller_code，則查詢子帳號對應的 seller_code
        if (!$sellerCode) {
            $subAccountTableName = $this->resourceConnection->getTableName('marketplace_sub_accounts');
            $sellerCodeSelect = $connection->select()
                ->from(['msa' => $subAccountTableName], [])
                ->joinLeft(
                    ['mu' => $marketplaceUserdataTableName],
                    'mu.seller_id = msa.seller_id',
                    ['seller_code']
                )
                ->where('msa.customer_id = ?', $customerId);
            $sellerCode = $connection->fetchOne($sellerCodeSelect);
        }

        return $sellerCode;
    }

    /**
     * @param $dir
     * @return bool
     */
    private function deleteDirectory($dir)
    {
        try {
            $directory = $this->fileSystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::TMP);
            if ($directory->isExist($dir)) {
                return $directory->delete($dir);
            }
        } catch (\Exception $e) {
            $this->logger->error('Delete Directory Error: ' . $e->getMessage());
        }
        return false;
    }
}
