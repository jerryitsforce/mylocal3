<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\SellerDocument\Api\Data;

interface FileSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get File list.
     * @return \Branch8\SellerDocument\Api\Data\FileInterface[]
     */
    public function getItems();

    /**
     * Set file_name list.
     * @param \Branch8\SellerDocument\Api\Data\FileInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

