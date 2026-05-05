<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ecpay\Invoice\Api\Data;

interface HotaiOrderInvoiceLogsSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get hotai_order_invoice_logs list.
     * @return \Ecpay\Invoice\Api\Data\HotaiOrderInvoiceLogsInterface[]
     */
    public function getItems();

    /**
     * Set order_id list.
     * @param \Ecpay\Invoice\Api\Data\HotaiOrderInvoiceLogsInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

