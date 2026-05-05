<?php

namespace HotaiConnected\FinancialReconciliation\Api;

/**
 * @api
 * @since 1.0.0
 */
interface MarketPlaceReconciliationManagementInterface
{
    /**
     * 取得廠商對帳資料。
     *
     * @param string|null $from 起始日期時間 (例如: '2025-01-01 00:00:00')
     * @param string|null $to 結束日期時間 (例如: '2025-11-01 00:00:00')
     * @param int $currentPage 目前頁碼 (預設為 1)
     * @param int $pageSize 每頁顯示數量 (預設為 20)
     * @return \Magento\Framework\Controller\Result\Raw
     */
    public function getCustomerReconciliation(?string $from = null, ?string $to = null, int $currentPage = 1, int $pageSize = 20);

    /**
     * 下載客戶的綠界賣家收入詳細資料。
     *
     * @param int $id 對帳單 ID
     * @return \Magento\Framework\Controller\Result\Raw
     */
    public function downloadCustomerEcpaySellerRevenueDetail(int $id);
}
