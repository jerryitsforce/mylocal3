<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ecpay\Invoice\Api\Data;

interface HotaiOrderItemInvoiceLogsSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get hotai_order_item_invoice_logs list.
     * @return \Ecpay\Invoice\Api\Data\HotaiOrderItemInvoiceLogsInterface[]
     */
    public function getItems();

    /**
     * Set hotai_order_invoice_log_id list.
     * @param \Ecpay\Invoice\Api\Data\HotaiOrderItemInvoiceLogsInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

