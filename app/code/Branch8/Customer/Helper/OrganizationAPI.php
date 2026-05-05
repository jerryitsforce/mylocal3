<?php

namespace Branch8\Customer\Helper;

class OrganizationAPI extends \Magento\Framework\App\Helper\AbstractHelper
{
    const AES_KEY = 'api_credential/organization/aes_key';

    const AES_IV = 'api_credential/organization/aes_iv';

    const HOTAI_EMP_ORG = 'api_credential/organization/hotai_emp';

    const EC_ORG = 'api_credential/organization/ec';

    protected $aesKey;

    protected $aesIv;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    ){
        parent::__construct($context);
        $this->aesKey = $this->scopeConfig->getValue(self::AES_KEY);
        $this->aesIv = $this->scopeConfig->getValue(self::AES_IV);

    }

    /**
     * @param $value
     * @return false|string
     */
    public function decryptData($value)
    {
        // Base64 decode the encrypted value
        $data = base64_decode($value);
        // Decrypt the data using OpenSSL
        $decrypted = openssl_decrypt($data, 'AES-256-CBC', $this->aesKey, OPENSSL_RAW_DATA, $this->aesIv);

        return $decrypted;
    }

    /**
     * @param $value
     * @return string
     */
    public function encryptData($value){
        $value = json_encode($value);
        $encrypted = openssl_encrypt($value, 'AES-256-CBC', $this->aesKey, OPENSSL_RAW_DATA, $this->aesIv);
        $encrypted = base64_encode($encrypted);
        return $encrypted;
    }

    /**
     * @return mixed
     */
    public function getHotaiEMP()
    {
        return $this->scopeConfig->getValue(self::HOTAI_EMP_ORG);
    }

    /**
     * @return mixed
     */
    public function getEcOrg()
    {
        return $this->scopeConfig->getValue(self::EC_ORG);
    }

}