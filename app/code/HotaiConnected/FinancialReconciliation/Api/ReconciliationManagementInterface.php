<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace HotaiConnected\FinancialReconciliation\Api;

use Magento\Framework\Webapi\Rest\Response;

use Magento\Framework\Controller\Result\Json;

interface ReconciliationManagementInterface
{

    /**
     * POST for reconciliation api
     * @param string $from
     * @param string $to
     * @param string $seller_code
     * @param string $batch_num
     * @param int $exclude_settled_orders
     * @param int $exclude_pending_gift_order
     * @param string $shipping_status
     * @param string $invoice_status
     * @param string $ticket_status
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function postReconciliation(
        string $from,
        string $to,
        string $seller_code,
        string $batch_num,
        string $shipping_status,
        string $invoice_status,
        string $ticket_status,
        int $exclude_settled_orders = 0,
        int $exclude_pending_gift_order = 0
    );

    /**
     * GET for reconciliation api
     * @param string|null $seller_code
     * @param string|null $from_date
     * @param string|null $to_date
     * @param int|null $settlement_status
     * @param string|null $batch_num
     * @param string|null $sales_person
     * @param string|null $tax_id
     * @param string|null $ticket_status
     * @param string|null $shipping_status
     * @param string|null $invoice_status
     * @param string|null $invoice_number
     * @param int|null $page_size
     * @param int|null $current_page
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function getReconciliation(
        string $seller_code = null,
        string $from_date = null,
        string $to_date = null,
        int $settlement_status = null,
        string $batch_num = null,
        string $sales_person = null,
        string $tax_id = null,
        string $ticket_status = null,
        string $shipping_status = null,
        string $invoice_status = null,
        string $invoice_number = null,
        int $page_size = 10,
        int $current_page = 1
    );

    /**
     * GET for reconciliation status api
    * @return \Magento\Framework\Controller\Result\Json
     */
    public function getStatus();

    /**
     * DELETE for reconciliation api
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function deleteReconciliation();

    /**
     * Download ECPay Seller Revenue Detail
     * @param string $ids Comma-separated list of reconciliation IDs
     * @return \Magento\Framework\Webapi\Rest\Response
     */
    public function downloadEcpaySellerRevenueDetail(string $ids);


    /**
     * Update settlement status for given reconciliation IDs.
     *
     * @param int[] $ids
     * @return bool
     */
    public function updateSettlementStatus(array $ids);

    /**
     * Add invoices to a financial reconciliation record.
     *
     * @param int $id
     * @param string[] $invoices
     * @return \Magento\Framework\Webapi\Rest\Response
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addInvoices(int $id, array $invoices);

    /**
     * Update invoices for a financial reconciliation record.
     *
     * @param int $id
     * @param string $old_invoice_number
     * @param string $new_invoice_number
     * @return \Magento\Framework\Webapi\Rest\Response
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function updateInvoices(int $id, string $old_invoice_number, string $new_invoice_number);

    /**
     * Delete an invoice from a financial reconciliation record.
     *
     * @return \Magento\Framework\Webapi\Rest\Response
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteInvoice();

     /**
     * POST for exception authorization with XLSX file upload.
     * @param int $id
     * @param string $file
     * @param string $reason
     * @return \Magento\Framework\Controller\Result\Json
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function exceptionAuth(int $id, string $file, string $reason);

    /**
     * GET for exception authorization.
     * @param string $ids Comma-separated list of financial reconciliation exception IDs.
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function getExceptionAuth(string $ids);

    /**
     * GET for listing exception authorizations.
     * @param string|null $batch_num
     * @param string|null $from_date
     * @param string|null $to_date
     * @param string|null $sales_person
     * @param string|null $seller_code
     * @param string|null $exception_status
     * @param int|null $page_size
     * @param int|null $current_page
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function listExceptionAuth(
        string $batch_num = null,
        string $from_date = null,
        string $to_date = null,
        string $sales_person = null,
        string $seller_code = null,
        string $exception_status = null,
        int $page_size = null,
        int $current_page = null
    );

    /**
     * POST for setting exception authorization status.
     * @param string $status
     * @param string[] $ids
     * @return \Magento\Framework\Controller\Result\Json
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setExceptionAuthStatus(string $status, array $ids);

    /**
     * POST for exporting exception authorizations to an XLSX file.
     * @param string[] $ids
     * @return \Magento\Framework\Webapi\Rest\Response
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function exportExceptionAuth(array $ids);

    /**
     * GET for exporting ticket data to an XLSX file.
     * @param string|null $start_date
     * @param string|null $end_date
     * @param string|null $type
     * @param string|null $order_created_from
     * @param string|null $order_created_to
     * @param string|null $event_created_from
     * @param string|null $event_created_to
     * @return \Magento\Framework\Webapi\Rest\Response
     */
    public function exportTicket(
        string $start_date = null,
        string $end_date = null,
        string $type = null,
        string $order_created_from = null,
        string $order_created_to = null,
        string $event_created_from = null,
        string $event_created_to = null
    );

    /**
     * GET for listing ticket data as a JSON response.
     * @param string|null $order_created_from
     * @param string|null $order_created_to
     * @param string|null $shop_title
     * @param string|null $ticket_status
     * @param string|null $order_status
     * @param string|null $serial_number
     * @param string|null $use_start_time
     * @param string|null $use_end_time
     * @param string|null $redeemed_from
     * @param string|null $redeemed_to
     * @param string|null $invoice_created_from
     * @param string|null $invoice_created_to
     * @param string|null $event_created_from
     * @param string|null $event_created_to
     * @param string|null $type
     * @param int|null $page_size
     * @param int|null $current_page
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function listTicket(
        string $order_created_from = null,
        string $order_created_to = null,
        string $shop_title = null,
        string $ticket_status = null,
        string $order_status = null,
        string $serial_number = null,
        string $use_start_time = null,
        string $use_end_time = null,
        string $redeemed_from = null,
        string $redeemed_to = null,
        string $invoice_created_from = null,
        string $invoice_created_to = null,
        string $type = null,
        string $event_created_from = null,
        string $event_created_to = null,
        int $page_size = null,
        int $current_page = null
    );

    /**
     * GET for listing ECPay order logs as a JSON response.
     * @param string|null $sales_person
     * @param string|null $seller_code
     * @param string|null $invoice_created_from
     * @param string|null $invoice_created_to
     * @param int|null $page_size
     * @param int|null $current_page
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function listOrder(
        string $sales_person = null,
        string $seller_code = null,
        string $invoice_created_from = null,
        string $invoice_created_to = null,
        int $page_size = null,
        int $current_page = null
    );

    /**
     * GET for exporting ECPay order logs as an XLSX file.
     * @param string|null $invoice_created_from
     * @param string|null $invoice_created_to
     * @param string|null $seller_code
     * @return void
     */
    public function exportOrder(
        string $invoice_created_from = null,
        string $invoice_created_to = null,
        string $seller_code = null
    );

    /**
     * POST for reconciliation ecpay seller revenue jobs
     * @param string[] $ids
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function exportEcpaySellerRevenueJobs(array $ids);
}