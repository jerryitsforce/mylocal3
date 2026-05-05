<?php

namespace Branch8\HotaiPay\Helper;

use Branch8\HotaiPay\Helper\Data as HelperData;

/**
 * Crypt
 */
class Crypt
{
    const ENCRYPT_CIPHER_ALGO = "AES-256-CBC";
    const CHAR_CODE_BASE64 = "base64";
    const CHAR_CODE_MD5 = "md5";

    private $iv;
    private $data;
    private $key;

    private $helperData;

    /**
     * __construct
     *
     * @return void
     */
    public function __construct(
        HelperData $helperData
    ) {
        $this->helperData = $helperData;
    }

    /**
     * encrypt
     *
     * @param  mixed $data
     * @param  mixed $charCode
     * @return void | string
     */
    public function encrypt(array $data, string $charCode)
    {
        $this->setData($data);
        $this->setCharCoding($charCode);

        $encryptText = openssl_encrypt(
            $this->data,
            self::ENCRYPT_CIPHER_ALGO,
            $this->key,
            OPENSSL_RAW_DATA,
            $this->iv
        );

        return $encryptText;
    }

    /**
     * decrypt
     *
     * @param  mixed $data
     * @param  mixed $charCode
     * @return void | string
     */
    public function decrypt(string $data, string $charCode)
    {
        $this->setCharCoding($charCode);

        $decryptText = openssl_decrypt(
            $data,
            self::ENCRYPT_CIPHER_ALGO,
            $this->key,
            false,
            $this->iv
        );

        return $decryptText;
    }


    /**
     * setData
     *
     * @param  mixed $data
     * @return void
     */
    public function setData(array $data)
    {
        $this->data = json_encode($data);
    }

    /**
     * setCharCoding
     *
     * @param  mixed $CharCode
     * @return void
     */
    public function setCharCoding($CharCode)
    {
        $this->initKeyAndIv();
        switch ($CharCode) {
            case self::CHAR_CODE_BASE64:
                $this->key = base64_decode($this->key);
                $this->iv = base64_decode($this->iv);
                break;
            case self::CHAR_CODE_MD5:
                // Use SHA-256 (raw binary) for key derivation to satisfy security requirements
                // Note: sha256 raw output = 32 bytes, appropriate for AES-256-CBC
                $this->key = hash('sha256', $this->key, true);
                $this->iv = substr(hash('sha256', $this->iv, true), 0, 16); // AES IV must be 16 bytes
                break;
            default:
                break;
        }
    }

    /**
     * initKeyAndIv
     *
     * @return void
     */
    public function initKeyAndIv()
    {
        $this->iv = $this->helperData->getAesIv();
        $this->key = $this->helperData->getAesKey();
    }
}
