<?php

namespace Branch8\EmailNotification\Cron;

use Branch8\EmailNotification\Helper\GeneralHelper;
use Branch8\EmailNotification\Helper\ScopeConfig;
use Branch8\HotaiCore\Service\MailService;
use Ecpay\General\Helper\Services\Config\MainService;
use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Zend_Db_Expr;
use Zend_Log_Exception;
use Branch8\SellerContactInformation\Helper\Data as SellerHelper;


class SellerNotificationCronJob
{
    public const TEMPLATE_ID = 'branch8_email_notification_seller_notification_template';

    protected GeneralHelper $_loggerInterface;
    protected MainService $_ecpayMainService;
    protected ResourceConnection $_resourceConnection;
    protected MailService $_mailService;
    protected ScopeConfig $_scopeConfig;
    protected SellerHelper $_sellerHelper;
    protected ProductRepositoryInterface $_productRepository;
    /**
     * @param GeneralHelper $loggerInterface
     * @param MainService $ecpayMainService
     * @param ResourceConnection $_resourceConnection
     * @param MailService $_mailService
     * @param ScopeConfig $_scopeConfig
     * @param SellerHelper $_sellerHelper
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        GeneralHelper $loggerInterface,
        MainService $ecpayMainService,
        ResourceConnection $_resourceConnection,
        MailService $_mailService,
        ScopeConfig $_scopeConfig,
        SellerHelper $_sellerHelper,
        ProductRepositoryInterface $productRepository,
    ) {
        $this->_loggerInterface    = $loggerInterface;
        $this->_ecpayMainService   = $ecpayMainService;
        $this->_resourceConnection = $_resourceConnection;
        $this->_mailService        = $_mailService;
        $this->_scopeConfig        = $_scopeConfig;
        $this->_sellerHelper       = $_sellerHelper;
        $this->_productRepository  = $productRepository;
    }

    /**
     * @return $this
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     * @throws LocalizedException
     */
    public function execute()
    {
        $this->writeLog(
            '-----------------------------Branch8 Email Notification Cron Job----------------------------------'
        );

        $sellers = $this->_sellerHelper->getAllSellerDataWithNotification();
        $this->writeLog("找到需要檢查的賣家數量: " . count($sellers));

        $totalSellers = count($sellers);
        $processedSellers = 0;
        $totalLowStockProducts = 0;
        $emailsSent = 0;

        foreach ($sellers as $seller) {
            try {
                $email       = $seller['email'];
                $lowStockQty = $seller['low_stock_quantity'];
                $products    = [];

                $this->writeLog("=== 開始檢查賣家 ID: {$seller['seller_id']} 的商品庫存 ===");
                $this->writeLog("賣家信箱: {$email}, 低庫存閾值: {$lowStockQty}");

                $sellerProducts = $this->getSellerProducts($seller['seller_id']);
                $this->writeLog("賣家總商品數量: " . count($sellerProducts));

                $productCount = 0;
                $lowStockCount = 0;

                foreach ($sellerProducts as $product) {
                    $this->writeLog("--- 商品 ID #".$product['mageproduct_id'].") ---");

                    $productCount++;
                    $productRepo = $this->_productRepository->getById($product['mageproduct_id']);
                    /** @var Product $productRepo */
                    $sku = $productRepo->getSku();
                    $productName = $productRepo->getName();
                    $status = $productRepo->getStatus();

                    if ($status === 0){
                        $this->writeLog("⚠ 商品狀態為禁用，跳過庫存檢查。SKU: {$sku}");
                        continue;
                    }

                    $this->writeLog("--- 商品 #{$productCount}: {$sku} ({$productName}) ---");

                    // 初始化庫存檢查變數
                    $stockItemQty = 0;
                    $stockMethod = '';
                    $stockDetails = [];

                    // 方法0: 優先檢查 wk_osi_variations 表 (多品項庫存)
                    $this->writeLog("嘗試方法0: wk_osi_variations 表查詢");
                    // Get all mage_pro_row_ids from getSellerProducts result and calculate total stock
                    $mageProRowIds = $product['mage_pro_row_ids'] ?? '';
                    $variationStock = $this->getVariationStockByRowIds($mageProRowIds);

                    if ($variationStock !== null) {
                        $stockItemQty = $variationStock;
                        $stockMethod = 'wk_osi_variations';
                        $stockDetails = ['qty' => $variationStock, 'source' => 'variation_table'];
                        $stockCheckSuccess = true;
                        $this->writeLog("✓ 方法0成功 - 變體庫存: {$stockItemQty}");
                    } else {
                        $this->writeLog("⚠ 方法0失敗: 未在 wk_osi_variations 表中找到庫存，使用 inventory_source_item 查詢");

                        // 方法1: 使用 inventory_source_item 和 inventory_reservation 表查詢
                        $this->writeLog("嘗試方法1: inventory_source_item 表查詢");
                        $inventoryStock = $this->getInventoryStockBySku($sku);

                        if ($inventoryStock !== null) {
                            $stockItemQty = $inventoryStock;
                            $stockMethod = 'inventory_source_item';
                            $stockDetails = ['qty' => $inventoryStock, 'source' => 'inventory_source_item'];
                            $stockCheckSuccess = true;
                            $this->writeLog("✓ 方法1成功 - 庫存數量: {$stockItemQty}");
                        } else {
                            $this->writeLog("❌ 方法1失敗: 未在 inventory_source_item 表中找到庫存");
                            $stockCheckSuccess = false;
                        }
                    }

                    // 記錄最終庫存檢查結果
                    $this->writeLog("最終結果 - 使用方法: {$stockMethod}, 庫存數量: {$stockItemQty}, 低庫存閾值: {$lowStockQty}");

                    // 記錄庫存檢查的詳細統計信息
                    if ($stockCheckSuccess) {
                        $this->writeLog("庫存檢查成功 - SKU: {$sku}, 方法: {$stockMethod}, 詳細數據: " . json_encode($stockDetails));
                    } else {
                        $this->writeLog("⚠ 庫存檢查失敗 - SKU: {$sku}, 無法獲取有效庫存數據");
                    }

                    if ($stockItemQty <= $lowStockQty) {
                        // 使用 SKU 作為 key 來避免重複產品
                        if (!isset($products[$sku])) {
                            $lowStockCount++;
                            $products[$sku] = [
                                'sku'           => $sku,
                                'product_name'  => $productName,
                                'stock'         => $stockItemQty,
                                'low_stock_qty' => $lowStockQty,
                                'stock_method'  => $stockMethod,  // 記錄使用的庫存檢查方法
                                'stock_details' => $stockDetails, // 記錄詳細的庫存數據
                            ];
                            $this->writeLog("🚨 低庫存警告: {$sku} - 庫存({$stockItemQty}) <= 閾值({$lowStockQty})");
                            $this->writeLog("   檢查方法: {$stockMethod}");
                            $this->writeLog("   詳細數據: " . json_encode($stockDetails));
                        } else {
                            $this->writeLog("⚠ 發現重複產品 SKU: {$sku}，已跳過");
                        }
                    } else {
                        $this->writeLog("✓ 庫存正常: {$sku} - 庫存({$stockItemQty}) > 閾值({$lowStockQty})");
                        $this->writeLog("   檢查方法: {$stockMethod}");
                    }

                    $this->writeLog("--- 商品 {$sku} 檢查完成 ---");
                }

                $this->writeLog("=== 賣家 {$seller['seller_id']} 庫存檢查完成 ===");
                $this->writeLog("總商品數: {$productCount}, 低庫存商品數: {$lowStockCount}");

                if (count($products) > 0) {
                    // 將關聯數組轉換為索引數組（保持原有格式）
                    $products = array_values($products);
                    $this->writeLog("去重後低庫存商品數: " . count($products));

                    $emails = explode(',', $email);

                    // 獲取賣家的 sub account emails
                    $subAccountEmails = $this->getSellerSubAccountEmails($seller['seller_id']);
                    if (!empty($subAccountEmails)) {
                        $this->writeLog("找到 " . count($subAccountEmails) . " 個 sub account emails");
                        $emails = [...$emails, ...$subAccountEmails];
                        // 移除重複的 email 地址
                        $emails = array_unique(array_filter(array_map('trim', $emails)));
                    }

                    // Always add a fixed notification email
                    $emails[] = 'chad@branch8.com';
                    $emails = array_unique($emails);

                    $this->writeLog("總共發送郵件到 " . count($emails) . " 個郵件地址");
                    $this->sendLowStockNotificationMail($emails, $products);
                    $emailsSent++;
                    $totalLowStockProducts += count($products);
                    $this->writeLog("📧 已發送低庫存通知郵件給賣家 {$seller['seller_id']}, 低庫存商品數: " . count($products));
                } else {
                    $this->writeLog("✓ 賣家 {$seller['seller_id']} 無低庫存商品，無需發送通知");
                }

                $processedSellers++;

            } catch (Exception $e) {
                $this->writeLog('❌ 處理賣家數據時發生錯誤: ' . $e->getMessage());
                $this->writeLog('錯誤堆疊: ' . $e->getTraceAsString());
                $processedSellers++;
                continue;
            }
        }

        // 最終摘要日誌
        $this->writeLog("==========================================");
        $this->writeLog("📊 庫存檢查任務完成摘要:");
        $this->writeLog("總賣家數: {$totalSellers}");
        $this->writeLog("已處理賣家數: {$processedSellers}");
        $this->writeLog("發送郵件數: {$emailsSent}");
        $this->writeLog("總低庫存商品數: {$totalLowStockProducts}");
        $this->writeLog("==========================================");

        return $this;
    }

    /**
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     */
    private function writeLog($message): void
    {
        $fileName = 'cron_email_notification_' . trim(date("Y_m_d"), '/');
        $this->_loggerInterface->writeLog($message, 'cron', $fileName);
    }

    /**
     * 查詢 wk_osi_variations（以多個 row_id 對應 product_id，使用 CONCAT 組合的字符串）
     * @param string $mageProRowIds 逗號分隔的 row_id 字符串
     * @return float|null
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     */
    private function getVariationStockByRowIds(string $mageProRowIds): ?float
    {
        try {
            if (empty($mageProRowIds)) {
                return null;
            }

            // Split comma-separated row_ids and filter valid integers
            $rowIds = array_filter(
                array_map('intval', explode(',', $mageProRowIds)),
                static function($id) {
                    return $id > 0;
                }
            );

            if (empty($rowIds)) {
                return null;
            }

            $connection = $this->_resourceConnection->getConnection();
            $select = $connection->select()
                ->from('wk_osi_variations', ['stock' => 'SUM(stock)'])
                ->where('product_id IN (?)', $rowIds);
            $result = $connection->fetchOne($select);

            if ($result !== null && $result > 0) {
                $this->writeLog("在 wk_osi_variations 表中找到庫存 (row_ids: {$mageProRowIds}): {$result}");
                return (float)$result;
            }
            $this->writeLog("在 wk_osi_variations 表中未找到庫存 (row_ids: {$mageProRowIds})");
            return null;
        } catch (Exception $e) {
            $this->writeLog("查詢 wk_osi_variations 表時發生錯誤: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Query inventory_source_item and inventory_reservation tables to get salable quantity
     * Uses subqueries to join source items with stock link and reservations
     * @param string $sku
     * @param int $stockId
     * @return float|null
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     */
    private function getInventoryStockBySku(string $sku, int $stockId = 1): ?float
    {
        try {
            if (empty($sku)) {
                return null;
            }
            $connection = $this->_resourceConnection->getConnection();

            // Subquery for source_items: sum quantity from inventory_source_item joined with inventory_source_stock_link
            $sourceItemsSubquery = $connection->select()
                ->from(
                    ['isi' => 'inventory_source_item'],
                    [
                        'sku' => 'isi.sku',
                        'qty' => new Zend_Db_Expr('SUM(isi.quantity)')
                    ]
                )
                ->join(
                    ['issl' => 'inventory_source_stock_link'],
                    'isi.source_code = issl.source_code',
                    []
                )
                ->where('isi.sku = ?', $sku)
                ->where('isi.status = ?', 1)
                ->where('issl.stock_id = ?', $stockId)
                ->group('isi.sku');

            // Subquery for reservations: sum quantity from inventory_reservation
            $reservationsSubquery = $connection->select()
                ->from(
                    ['ir' => 'inventory_reservation'],
                    [
                        'sku' => 'ir.sku',
                        'qty' => new Zend_Db_Expr('SUM(ir.quantity)')
                    ]
                )
                ->where('ir.sku = ?', $sku)
                ->where('ir.stock_id = ?', $stockId)
                ->group('ir.sku');

            // Main query: join source_items with reservations and calculate salable_qty
            $select = $connection->select()
                ->from(
                    ['source_items' => $sourceItemsSubquery],
                    [
                        'salable_qty' => new Zend_Db_Expr('(COALESCE(source_items.qty, 0) + COALESCE(reservations.qty, 0))')
                    ]
                )
                ->joinLeft(
                    ['reservations' => $reservationsSubquery],
                    'source_items.sku = reservations.sku',
                    []
                );

            $result = $connection->fetchOne($select);
            if ($result !== null && $result !== false) {
                $this->writeLog("在 inventory_source_item 表中找到庫存: {$result}");
                return (float)$result;
            }
            $this->writeLog("在 inventory_source_item 表中未找到庫存");
            return null;
        } catch (Exception $e) {
            $this->writeLog("查詢 inventory_source_item 表時發生錯誤: " . $e->getMessage());
            return null;
        }
    }

    /**
     * 獲取賣家的所有 sub account emails
     *
     * @param int $sellerId
     * @return array 返回 email 地址數組
     * @throws Zend_Log_Exception|FileSystemException
     */
    private function getSellerSubAccountEmails($sellerId): array
    {
        try {
            $connection = $this->_resourceConnection->getConnection();

            $select = $connection->select()
                ->from(['msa' => 'marketplace_sub_accounts'], [])
                ->join(
                    ['c' => 'customer_entity'],
                    'msa.customer_id = c.entity_id',
                    ['email' => 'c.email']
                )
                ->where('msa.seller_id = ?', $sellerId)
                ->where('msa.status = ?', 1);

            $results = $connection->fetchAll($select);

            if (empty($results)) {
                $this->writeLog("賣家 {$sellerId} 沒有找到 sub account emails");
                return [];
            }

            $emails = array_column($results, 'email');
            $this->writeLog("賣家 {$sellerId} 找到 " . count($emails) . " 個 sub account emails: " . implode(', ', $emails));

            return $emails;
        } catch (Exception $e) {
            $this->writeLog("查詢 sub account emails 時發生錯誤: " . $e->getMessage());
            return [];
        }
    }

    /**
     * @param $sellerId
     * @return array
     */
    private function getSellerProducts($sellerId): array
    {
        $connection = $this->_resourceConnection->getConnection();

        $select = $connection->select()
            ->from(
                'marketplace_product',
                [
                    'mageproduct_id',
                    'mage_pro_row_ids' => new Zend_Db_Expr('GROUP_CONCAT(DISTINCT mage_pro_row_id ORDER BY mage_pro_row_id SEPARATOR ",")')
                ]
            )
            ->where('seller_id = ?', $sellerId)
            ->where('status in (0, 1, 2)')
            ->group('mageproduct_id');

        return $connection->fetchAll($select) ?: [];
    }

    /**
     * @param $emails
     * @param $products
     * @return void
     * @throws FileSystemException
     * @throws Zend_Log_Exception
     */
    private function sendLowStockNotificationMail($emails, $products): void
    {
        $this->_mailService
            ->setTemplateId(self::TEMPLATE_ID)
            ->setReceiverEmail($emails)
            ->send([
                'products' => $products,
            ]);
    }
}
