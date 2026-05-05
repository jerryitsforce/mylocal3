<?php

namespace Branch8\Catalog\Api;

interface ViewedRequestInterface
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
     * @return array
     */
    public function getSku();

    /**
     * @param array $sku
     * @return this
     */
    public function setSku( string $sku);
}