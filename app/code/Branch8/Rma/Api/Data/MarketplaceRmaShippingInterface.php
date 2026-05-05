<?php
/**
 * Copyright © dev@branch8.com All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Rma\Api\Data;

interface MarketplaceRmaShippingInterface
{

    const PARENT_ID = 'parent_id';
    const MARKETPLACERMASHIPPING_ID = 'marketplacermashipping_id';
    const SHIPPING_NUMBER = 'shipping_number';
    const STATUS = 'status';

    /**
     * Get marketplacermashipping_id
     * @return string|null
     */
    public function getMarketplacermashippingId();

    /**
     * Set marketplacermashipping_id
     * @param string $marketplacermashippingId
     * @return \Branch8\Rma\MarketplaceRmaShipping\Api\Data\MarketplaceRmaShippingInterface
     */
    public function setMarketplacermashippingId($marketplacermashippingId);

    /**
     * Get parent_id
     * @return string|null
     */
    public function getParentId();

    /**
     * Set parent_id
     * @param string $parentId
     * @return \Branch8\Rma\MarketplaceRmaShipping\Api\Data\MarketplaceRmaShippingInterface
     */
    public function setParentId($parentId);
}

