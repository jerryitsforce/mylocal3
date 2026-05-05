<?php

namespace Branch8\Catalog\Api;
use Branch8\Catalog\Api\WishlistResponseInterface;

interface ProductInterface
{
    /**
     * @param string $member_seq
     * @param mixed $sku
     * @param int $storeId
     * @return \Branch8\Catalog\Api\ViewedResponseInterface
     */
    public function addViewedProduct(string $member_seq, mixed $sku, int $storeId = 1): ViewedResponseInterface;

    /**
     * @param string $member_seq
     * @param string $sku
     * @param int $storeId
     * @return \Branch8\Catalog\Api\WishlistResponseInterface
     */
    public function addWishlist(string $member_seq, mixed $sku, int $storeId = 1): WishlistResponseInterface;
}
