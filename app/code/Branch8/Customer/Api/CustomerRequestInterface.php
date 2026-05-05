<?php

namespace Branch8\Customer\Api;

interface CustomerRequestInterface
{
    const MEMBER_SEQ = 'member_seq';
    const HOTAI1_NAME = 'hotai1_name';
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
    public function getHotai1Name();

    /**
     * @param string $hotai1Name
     * @return this
     */
    public function setHotai1Name( string $hotai1Name);
}