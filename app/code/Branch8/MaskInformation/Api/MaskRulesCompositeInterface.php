<?php

namespace Branch8\MaskInformation\Api;

interface MaskRulesCompositeInterface
{
    /**
     * @param $code
     * @param $inputValue
     * @return string
     */
    public function mask($code, $inputValue): string;
}
