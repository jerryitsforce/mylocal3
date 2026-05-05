<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\BrandManagement\Api;

/**
 * Brand Option Repository Interface
 */
interface BrandOptionRepositoryInterface
{
    /**
     * Get brand options list
     *
     * @return array
     */
    public function getBrandOptions();

    /**
     * Get brand option by option ID
     *
     * @param int $optionId
     * @return array|null
     */
    public function getBrandOptionById($optionId);

    /**
     * Create new brand option
     *
     * @param string $brandName
     * @param int $sortOrder
     * @return int Option ID
     * @throws \Exception
     */
    public function createBrandOption($brandName, $sortOrder = 0);

    /**
     * Update brand option
     *
     * @param int $optionId
     * @param string $brandName
     * @param int $sortOrder
     * @return bool
     * @throws \Exception
     */
    public function updateBrandOption($optionId, $brandName, $sortOrder = 0);

    /**
     * Delete brand option
     *
     * @param int $optionId
     * @return bool
     * @throws \Exception
     */
    public function deleteBrandOption($optionId);

    /**
     * Check if brand option is used by products
     *
     * @param int $optionId
     * @return bool
     */
    public function isBrandOptionUsed($optionId);
}
