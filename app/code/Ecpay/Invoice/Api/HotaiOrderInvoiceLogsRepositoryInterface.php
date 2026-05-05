<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ecpay\Invoice\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface HotaiOrderInvoiceLogsRepositoryInterface
{

    /**
     * Save hotai_order_invoice_logs
     * @param \Ecpay\Invoice\Api\Data\HotaiOrderInvoiceLogsInterface $hotaiOrderInvoiceLogs
     * @return \Ecpay\Invoice\Api\Data\HotaiOrderInvoiceLogsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Ecpay\Invoice\Api\Data\HotaiOrderInvoiceLogsInterface $hotaiOrderInvoiceLogs
    );

    /**
     * Retrieve hotai_order_invoice_logs
     * @param string $hotaiOrderInvoiceLogsId
     * @return \Ecpay\Invoice\Api\Data\HotaiOrderInvoiceLogsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($hotaiOrderInvoiceLogsId);

    /**
     * Retrieve hotai_order_invoice_logs matching the specified criteria.
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Ecpay\Invoice\Api\Data\HotaiOrderInvoiceLogsSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete hotai_order_invoice_logs
     * @param \Ecpay\Invoice\Api\Data\HotaiOrderInvoiceLogsInterface $hotaiOrderInvoiceLogs
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Ecpay\Invoice\Api\Data\HotaiOrderInvoiceLogsInterface $hotaiOrderInvoiceLogs
    );

    /**
     * Delete hotai_order_invoice_logs by ID
     * @param string $hotaiOrderInvoiceLogsId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($hotaiOrderInvoiceLogsId);
}

