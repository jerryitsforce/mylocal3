<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\MarketplaceStaging\Block\Widget;

/**
 * @api
 * @since 100.0.2
 */
interface ContainerInterface
{
    /**
     * Public wrapper for the button list
     *
     * @param string $buttonId
     * @param array $data
     * @param integer $level
     * @param integer $sortOrder
     * @param string|null $region That button should be displayed in ('toolbar', 'header', 'footer', null)
     * @return $this
     */
    public function addButton($buttonId, $data, $level = 0, $sortOrder = 0, $region = 'toolbar');

    /**
     * Public wrapper for the button list
     *
     * @param string $buttonId
     * @return $this
     */
    public function removeButton($buttonId);

    /**
     * Public wrapper for protected _updateButton method
     *
     * @param string $buttonId
     * @param string|null $key
     * @param string $data
     * @return $this
     */
    public function updateButton($buttonId, $key, $data);

    /**
     * Check whether button rendering is allowed in current context
     *
     * @param \Branch8\MarketplaceStaging\Block\Widget\Button\Item $item
     * @return bool
     */
    public function canRender(\Branch8\MarketplaceStaging\Block\Widget\Button\Item $item);
}
