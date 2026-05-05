<?php

namespace Branch8\MaskInformation\Api;

interface MaskRuleActionInterface
{
    /**
     * @param $inputValue
     * @return string
     */
    public function execute($inputValue): string;
}
