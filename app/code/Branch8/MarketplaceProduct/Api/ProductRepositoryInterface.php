<?php
/**
 * Webkul Software.
 *
 * @category Webkul
 * @package Webkul_MpApi
 * @author Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license https://store.webkul.com/license.html
 */

namespace Branch8\MarketplaceProduct\Api;

/**
 * ProductRepository CRUD Interface
 */
interface ProductRepositoryInterface
{
    /**
     * Create product
     *
     * @param int $id Seller Id
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param bool $saveOptions
     * @return \Magento\Catalog\Api\Data\ProductInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function saveSellerProduct($id, \Magento\Catalog\Api\Data\ProductInterface $product, $saveOptions = false);
}
