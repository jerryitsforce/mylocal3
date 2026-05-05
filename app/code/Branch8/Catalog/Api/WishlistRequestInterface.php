<?php

namespace Branch8\Catalog\Api;

interface WishlistRequestInterface
{
    const MEMBER_SEQ = 'member_seq';
    const SKU = 'sku';
    /**
     * @return string
     */
    public function getMemberSeq();

    /**
     * @param string $memberSeq
     * @return this
     */
    public function setMemberSeq( string $memberSeq);
    /**
     * @return string
     */
    public function getSku();

    /**
     * @param string $sku
     * @return this
     */
    public function setSku( string $sku);
}