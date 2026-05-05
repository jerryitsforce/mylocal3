<?php

namespace Branch8\HotaiPoint\Helper;

use Magento\Framework\HTTP\Client\Curl as OriMagentoCurl;

class Curl extends OriMagentoCurl
{
    protected $_errno;

    /**
     * @inheritDoc
     */
    public function get($uri)
    {
        $this->_errno = null;
        $this->makeRequest("GET", $uri);
    }

    /**
     * @inheritDoc
     */
    public function post($uri, $params)
    {
        $this->_errno = null;
        $this->makeRequest("POST", $uri, $params);
    }

    /**
     * @inheritDoc
     */
    public function doError($string)
    {
        //  phpcs:ignore Magento2.Exceptions.DirectThrow
        $this->_errno = curl_errno($this->_ch);
        throw new \Exception($string);
    }

    public function getErrno()
    {
        return $this->_errno;
    }
}
