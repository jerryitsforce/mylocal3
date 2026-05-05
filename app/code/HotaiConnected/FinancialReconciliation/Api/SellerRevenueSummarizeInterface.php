<?php
declare(strict_types=1);

namespace HotaiConnected\FinancialReconciliation\Api;

/**
 * Interface SellerRevenueSummarizeInterface
 * @api
 */
interface SellerRevenueSummarizeInterface
{
    /**
     * Get seller revenue summarize report.
     * @param string $ids
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function getSellerRevenueSummarize(string $ids): \Magento\Framework\App\ResponseInterface;

    /**
     * Get seller revenue summarize report for all sellers.
     * @param string $invoice_from
     * @param string $invoice_to
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function getSellerRevenueSummarizeAll(string $invoice_from, string $invoice_to): \Magento\Framework\App\ResponseInterface;
}