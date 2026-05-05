<?php

namespace Branch8\WebkulMpBuyerSellerChat\Model;

class Until
{
    /**
     * @param $prefix
     * @return string
     */
    static function generateUnique($prefix)
    {
        return uniqid($prefix);
    }
}
