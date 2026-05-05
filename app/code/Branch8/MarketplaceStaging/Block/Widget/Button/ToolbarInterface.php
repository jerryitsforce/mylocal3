<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\MarketplaceStaging\Block\Widget\Button;

/**
 * Interface \Branch8\MarketplaceStaging\Block\Widget\Button\ToolbarInterface
 *
 * @api
 */
interface ToolbarInterface
{
    /**
     * Push buttons into toolbar
     *
     * @param \Magento\Framework\View\Element\AbstractBlock $context
     * @param \Branch8\MarketplaceStaging\Block\Widget\Button\ButtonList $buttonList
     * @return void
     */
    public function pushButtons(
        \Magento\Framework\View\Element\AbstractBlock $context,
        \Branch8\MarketplaceStaging\Block\Widget\Button\ButtonList $buttonList
    );
}
