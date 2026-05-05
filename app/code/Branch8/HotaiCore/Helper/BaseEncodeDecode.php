<?php

namespace Branch8\HotaiCore\Helper;

class BaseEncodeDecode
{
    /**
     * @param $base10
     * @return string
     */
    public function base36Encode($base10): string
    {
        return $this->baseConvert($base10, 10, 36);
    }

    /**
     * @param $base36
     * @return string
     */
    public  function base36Decode($base36): string
    {
        return $this->baseConvert($base36, 36, 10);
    }

    /**
     * @param $base
     * @param $fromBase
     * @param $toBase
     * @return string
     */
    private function baseConvert($base, $fromBase, $toBase): string
    {
        return base_convert($base, $fromBase, $toBase);
    }
}
