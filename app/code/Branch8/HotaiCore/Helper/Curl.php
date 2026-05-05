<?php

namespace Branch8\HotaiCore\Helper;

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

    public function put($uri, $params)
    {
        $this->_errno = null;
        $this->_ch = curl_init();
        $this->curlOption(CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS | CURLPROTO_FTP | CURLPROTO_FTPS);
        $this->curlOption(CURLOPT_URL, $uri);

        $this->curlOption(CURLOPT_POST, 1);
        $this->curlOption(CURLOPT_POSTFIELDS, is_array($params) ? http_build_query($params) : $params);
        $this->curlOption(CURLOPT_CUSTOMREQUEST, "PUT");

        if (count($this->_headers)) {
            $heads = [];
            foreach ($this->_headers as $k => $v) {
                $heads[] = $k . ': ' . $v;
            }
            $this->curlOption(CURLOPT_HTTPHEADER, $heads);
        }

        if (count($this->_cookies)) {
            $cookies = [];
            foreach ($this->_cookies as $k => $v) {
                $cookies[] = "{$k}={$v}";
            }
            $this->curlOption(CURLOPT_COOKIE, implode(";", $cookies));
        }

        if ($this->_timeout) {
            $this->curlOption(CURLOPT_TIMEOUT, $this->_timeout);
        }

        if ($this->_port != 80) {
            $this->curlOption(CURLOPT_PORT, $this->_port);
        }

        $this->curlOption(CURLOPT_RETURNTRANSFER, 1);
        $this->curlOption(CURLOPT_HEADERFUNCTION, [$this, 'parseHeaders']);

        // if ($this->sslVersion !== null) {
        //     $this->curlOption(CURLOPT_SSLVERSION, $this->sslVersion);
        // }

        if (count($this->_curlUserOptions)) {
            foreach ($this->_curlUserOptions as $k => $v) {
                $this->curlOption($k, $v);
            }
        }

        $this->_headerCount = 0;
        $this->_responseHeaders = [];
        $this->_responseBody = curl_exec($this->_ch);
        $err = curl_errno($this->_ch);
        if ($err) {
            $this->doError(curl_error($this->_ch));
        }
        curl_close($this->_ch);
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

    public function getCurlInfo()
    {
        return curl_getinfo($this->_ch);
    }
}
