<?php

namespace Branch8\Customer\Api;

interface CustomerResponseInterface
{
    const RESULT = 'result';

    /**
     * @return string
     */
    public function getResult();

    /**
     * @param string $result
     * @return this
     */
    public function setResult(string $result);

}