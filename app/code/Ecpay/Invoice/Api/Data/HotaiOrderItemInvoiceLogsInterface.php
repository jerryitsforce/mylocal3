<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ecpay\Invoice\Api\Data;

interface HotaiOrderItemInvoiceLogsInterface
{

    const ORDER_ITEM_NAME = 'order_item_name';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const TAX = 'tax';
    const QTY = 'qty';
    const HOTAI_ORDER_INVOICE_LOG_ID = 'hotai_order_invoice_log_id';
    const EXCLUDE_TAX = 'exclude_tax';
    const HOTAI_ORDER_ITEM_INVOICE_LOGS_ID = 'hotai_order_item_invoice_logs_id';
    const TYPE = 'type';
    const INCLUDE_TAX = 'include_tax';
    const ORDER_ITEM_ID = 'order_item_id';
    const EXPORT_REPORT = 'export_report';
    const DISCOUNT_AMOUNT_INCLUDE_TAX = 'discount_amount_include_tax';
    const DISCOUNT_AMOUNT_EXCLUDE_TAX = 'discount_amount_exclude_tax';
    const DISCOUNT_AMOUNT_TAX = 'discount_amount_tax';

    /**
     * Get hotai_order_item_invoice_logs_id
     * @return string|null
     */
    public function getHotaiOrderItemInvoiceLogsId();

    /**
     * Set hotai_order_item_invoice_logs_id
     * @param string $hotaiOrderItemInvoiceLogsId
     * @return \Ecpay\Invoice\HotaiOrderItemInvoiceLogs\Api\Data\HotaiOrderItemInvoiceLogsInterface
     */
    public function setHotaiOrderItemInvoiceLogsId($hotaiOrderItemInvoiceLogsId);

    /**
     * Get hotai_order_invoice_log_id
     * @return string|null
     */
    public function getHotaiOrderInvoiceLogId();

    /**
     * Set hotai_order_invoice_log_id
     * @param string $hotaiOrderInvoiceLogId
     * @return \Ecpay\Invoice\HotaiOrderItemInvoiceLogs\Api\Data\HotaiOrderItemInvoiceLogsInterface
     */
    public function setHotaiOrderInvoiceLogId($hotaiOrderInvoiceLogId);

    /**
     * Get order_item_id
     * @return string|null
     */
    public function getOrderItemId();

    /**
     * Set order_item_id
     * @param string $orderItemId
     * @return \Ecpay\Invoice\HotaiOrderItemInvoiceLogs\Api\Data\HotaiOrderItemInvoiceLogsInterface
     */
    public function setOrderItemId($orderItemId);

    /**
     * Get order_item_name
     * @return string|null
     */
    public function getOrderItemName();

    /**
     * Set order_item_name
     * @param string $orderItemName
     * @return \Ecpay\Invoice\HotaiOrderItemInvoiceLogs\Api\Data\HotaiOrderItemInvoiceLogsInterface
     */
    public function setOrderItemName($orderItemName);

    /**
     * Get created_at
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created_at
     * @param string $createdAt
     * @return \Ecpay\Invoice\HotaiOrderItemInvoiceLogs\Api\Data\HotaiOrderItemInvoiceLogsInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * Get updated_at
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Set updated_at
     * @param string $updatedAt
     * @return \Ecpay\Invoice\HotaiOrderItemInvoiceLogs\Api\Data\HotaiOrderItemInvoiceLogsInterface
     */
    public function setUpdatedAt($updatedAt);

    /**
     * Get qty
     * @return string|null
     */
    public function getQty();

    /**
     * Set qty
     * @param string $qty
     * @return \Ecpay\Invoice\HotaiOrderItemInvoiceLogs\Api\Data\HotaiOrderItemInvoiceLogsInterface
     */
    public function setQty($qty);

    /**
     * Get include_tax
     * @return string|null
     */
    public function getIncludeTax();

    /**
     * Set include_tax
     * @param string $includeTax
     * @return \Ecpay\Invoice\HotaiOrderItemInvoiceLogs\Api\Data\HotaiOrderItemInvoiceLogsInterface
     */
    public function setIncludeTax($includeTax);

    /**
     * Get exclude_tax
     * @return string|null
     */
    public function getExcludeTax();

    /**
     * Set exclude_tax
     * @param string $excludeTax
     * @return \Ecpay\Invoice\HotaiOrderItemInvoiceLogs\Api\Data\HotaiOrderItemInvoiceLogsInterface
     */
    public function setExcludeTax($excludeTax);

    /**
     * Get tax
     * @return string|null
     */
    public function getTax();

    /**
     * Set tax
     * @param string $tax
     * @return \Ecpay\Invoice\HotaiOrderItemInvoiceLogs\Api\Data\HotaiOrderItemInvoiceLogsInterface
     */
    public function setTax($tax);

    /**
     * Get type
     * @return string|null
     */
    public function getType();

    /**
     * Set type
     * @param string $type
     * @return \Ecpay\Invoice\HotaiOrderItemInvoiceLogs\Api\Data\HotaiOrderItemInvoiceLogsInterface
     */
    public function setType($type);

    /**
     * Get export_report
     * @return string|null
     */
    public function getExportReport();

    /**
     * Set export_report
     * @param string $exportReport
     * @return \Ecpay\Invoice\HotaiOrderItemInvoiceLogs\Api\Data\HotaiOrderItemInvoiceLogsInterface
     */
    public function setExportReport($exportReport);

    /**
     * Get discount_amount_include_tax
     * @return string|null
     */
    public function getDiscountAmountIncludeTax();

    /**
     * Set discount_amount_include_tax
     * @param $discountAmountIncludeTax
     * @return \Ecpay\Invoice\HotaiOrderItemInvoiceLogs\Api\Data\HotaiOrderItemInvoiceLogsInterface
     */
    public function setDiscountAmountIncludeTax($discountAmountIncludeTax);

    /**
     * Get discount_amount_exclude_tax
     * @return string|null
     */
    public function getDiscountAmountExcludeTax();

    /**
     * Set discount_amount_exclude_tax
     * @param $discountAmountExcludeTax
     * @return \Ecpay\Invoice\HotaiOrderItemInvoiceLogs\Api\Data\HotaiOrderItemInvoiceLogsInterface
     */
    public function setDiscountAmountExcludeTax($discountAmountExcludeTax);

    /**
     * Get discount_amount_tax
     * @return string|null
     */
    public function getDiscountAmountTax();

    /**
     * Set discount_amount_tax
     * @param $discountAmountTax
     * @return \Ecpay\Invoice\HotaiOrderItemInvoiceLogs\Api\Data\HotaiOrderItemInvoiceLogsInterface
     */
    public function setDiscountAmountTax($discountAmountTax);
}

