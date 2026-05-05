<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ecpay\Invoice\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface HotaiOrderItemInvoiceLogsRepositoryInterface
{

    /**
     * Save hotai_order_item_invoice_logs
     * @param \Ecpay\Invoice\Api\Data\HotaiOrderItemInvoiceLogsInterface $hotaiOrderItemInvoiceLogs
     * @return \Ecpay\Invoice\Api\Data\HotaiOrderItemInvoiceLogsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Ecpay\Invoice\Api\Data\HotaiOrderItemInvoiceLogsInterface $hotaiOrderItemInvoiceLogs
    );

    /**
     * Retrieve hotai_order_item_invoice_logs
     * @param string $hotaiOrderItemInvoiceLogsId
     * @return \Ecpay\Invoice\Api\Data\HotaiOrderItemInvoiceLogsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($hotaiOrderItemInvoiceLogsId);

    /**
     * Retrieve hotai_order_item_invoice_logs matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Ecpay\Invoice\Api\Data\HotaiOrderItemInvoiceLogsSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete hotai_order_item_invoice_logs
     * @param \Ecpay\Invoice\Api\Data\HotaiOrderItemInvoiceLogsInterface $hotaiOrderItemInvoiceLogs
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Ecpay\Invoice\Api\Data\HotaiOrderItemInvoiceLogsInterface $hotaiOrderItemInvoiceLogs
    );

    /**
     * Delete hotai_order_item_invoice_logs by ID
     * @param string $hotaiOrderItemInvoiceLogsId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($hotaiOrderItemInvoiceLogsId);
}

