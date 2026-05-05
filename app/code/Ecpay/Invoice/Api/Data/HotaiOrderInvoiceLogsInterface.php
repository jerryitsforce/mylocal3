<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ecpay\Invoice\Api\Data;

interface HotaiOrderInvoiceLogsInterface
{

    const CREATED_AT                  = 'created_at';
    const UPDATED_AT                  = 'updated_at';
    const TAX                         = 'tax';
    const EXCLUDE_TAX                 = 'exclude_tax';
    const INVOICE_NUMBER              = 'invoice_number';
    const HOTIA_CHECKOUT_NUMBER       = 'hotai_checkout_number';
    const POINT_USED                  = 'point_used';
    const ORDER_ID                    = 'order_id';
    const HOTAI_ORDER_INVOICE_LOGS_ID = 'hotai_order_invoice_logs_id';
    const INCLUDE_TAX                 = 'include_tax';
    const STATUS                      = 'status';
    const INVOICE_COUNT               = 'invoice_count';
    const IS_REVERSE                  = 'is_reverse';
    const IS_CROSS_MONTH              = 'is_cross_month';

    /**
     * Get hotai_order_invoice_logs_id
     * @return string|null
     */
    public function getHotaiOrderInvoiceLogsId();

    /**
     * Set hotai_order_invoice_logs_id
     * @param string $hotaiOrderInvoiceLogsId
     * @return \Ecpay\Invoice\HotaiOrderInvoiceLogs\Api\Data\HotaiOrderInvoiceLogsInterface
     */
    public function setHotaiOrderInvoiceLogsId($hotaiOrderInvoiceLogsId);

    /**
     * Get order_id
     * @return string|null
     */
    public function getOrderId();

    /**
     * Set order_id
     * @param string $orderId
     * @return \Ecpay\Invoice\HotaiOrderInvoiceLogs\Api\Data\HotaiOrderInvoiceLogsInterface
     */
    public function setOrderId($orderId);

    /**
     * Get invoice_number
     * @return string|null
     */
    public function getInvoiceNumber();

    /**
     * Set invoice_number
     * @param string $invoiceNumber
     * @return \Ecpay\Invoice\HotaiOrderInvoiceLogs\Api\Data\HotaiOrderInvoiceLogsInterface
     */
    public function setInvoiceNumber($invoiceNumber);

    /**
     * Get invoice_number
     * @return string|null
     */
    public function getHotaiCheckoutNumber();

    /**
     * Set hotai_checkout_number
     * @param string $hotaiCheckoutNumber
     * @return \Ecpay\Invoice\HotaiOrderInvoiceLogs\Api\Data\HotaiOrderInvoiceLogsInterface
     */
    public function setHotaiCheckoutNumber($hotaiCheckoutNumber);

    /**
     * Get status
     * @return string|null
     */
    public function getStatus();

    /**
     * Set status
     * @param string $status
     * @return \Ecpay\Invoice\HotaiOrderInvoiceLogs\Api\Data\HotaiOrderInvoiceLogsInterface
     */
    public function setStatus($status);

    /**
     * Get status
     * @return string|null
     */
    public function getInvoiceCount();

    /**
     * Set status
     * @param string $invoiceCount
     * @return \Ecpay\Invoice\HotaiOrderInvoiceLogs\Api\Data\HotaiOrderInvoiceLogsInterface
     */
    public function setInvoiceCount($invoiceCount);

    /**
     * Get status
     * @return string|null
     */
    public function getIsReverse();

    /**
     * Set status
     * @param string $isReverse
     * @return \Ecpay\Invoice\HotaiOrderInvoiceLogs\Api\Data\HotaiOrderInvoiceLogsInterface
     */
    public function setIsReverse($isReverse);

    /**
     * Get status
     * @return string|null
     */
    public function getIsCrossMonth();

    /**
     * Set status
     * @param string $isCrossMonth
     * @return \Ecpay\Invoice\HotaiOrderInvoiceLogs\Api\Data\HotaiOrderInvoiceLogsInterface
     */
    public function setIsCrossMonth($isCrossMonth);

    /**
     * Get created_at
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created_at
     * @param string $createdAt
     * @return \Ecpay\Invoice\HotaiOrderInvoiceLogs\Api\Data\HotaiOrderInvoiceLogsInterface
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
     * @return \Ecpay\Invoice\HotaiOrderInvoiceLogs\Api\Data\HotaiOrderInvoiceLogsInterface
     */
    public function setUpdatedAt($updatedAt);

    /**
     * Get point_used
     * @return string|null
     */
    public function getPointUsed();

    /**
     * Set point_used
     * @param string $pointUsed
     * @return \Ecpay\Invoice\HotaiOrderInvoiceLogs\Api\Data\HotaiOrderInvoiceLogsInterface
     */
    public function setPointUsed($pointUsed);

    /**
     * Get include_tax
     * @return string|null
     */
    public function getIncludeTax();

    /**
     * Set include_tax
     * @param string $includeTax
     * @return \Ecpay\Invoice\HotaiOrderInvoiceLogs\Api\Data\HotaiOrderInvoiceLogsInterface
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
     * @return \Ecpay\Invoice\HotaiOrderInvoiceLogs\Api\Data\HotaiOrderInvoiceLogsInterface
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
     * @return \Ecpay\Invoice\HotaiOrderInvoiceLogs\Api\Data\HotaiOrderInvoiceLogsInterface
     */
    public function setTax($tax);
}

