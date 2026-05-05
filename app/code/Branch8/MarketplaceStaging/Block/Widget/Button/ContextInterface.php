<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\MarketplaceStaging\Block\Widget\Button;

/**
 * Interface \Branch8\MarketplaceStaging\Block\Widget\Button\ContextInterface
 *
 * @api
 */
interface ContextInterface
{
    /**
     * Check whether button rendering is allowed in current context
     *
     * @param \Branch8\MarketplaceStaging\Block\Widget\Button\Item $item
     * @return bool
     */
    public function canRender(\Branch8\MarketplaceStaging\Block\Widget\Button\Item $item);
}
