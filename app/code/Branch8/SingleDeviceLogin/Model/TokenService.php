<?php

namespace Branch8\SingleDeviceLogin\Model;

use Magento\Eav\Model\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Math\Random;

class TokenService
{
    const ATTRIBUTE_CODE = 'latest_hotai_token';

    private $attribute = null;
    /**
     * @var Encryptor
     */
    private Encryptor $encryptor;
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    private Config $eavConfig;
    private \Branch8\HotaiAuth\Service\HotaiAuthService $hotaiService;
    private Random $random;

    /**
     * @param ResourceConnection $resourceConnection
     * @param Encryptor $encryptor
     * @param \Branch8\HotaiAuth\Service\HotaiAuthService $hotaiAuthService
     * @param Config $eavConfig
     * @param Random $random
     */
    public function __construct(
        ResourceConnection                          $resourceConnection,
        Encryptor                                   $encryptor,
        \Branch8\HotaiAuth\Service\HotaiAuthService $hotaiAuthService,
        Config                                      $eavConfig,
        Random                                      $random
    )
    {
        $this->random = $random;
        $this->hotaiService = $hotaiAuthService;
        $this->eavConfig = $eavConfig;
        $this->resourceConnection = $resourceConnection;
        $this->encryptor = $encryptor;
    }

    /**
     * @param string $token
     * @return string
     */
    public function hashHotaiToken(string $token)
    {
        return $this->encryptor->encryptWithFastestAvailableAlgorithm($token);
    }

    /**
     * @param $customerId
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getLastestHotaiToken($customerId)
    {
        /**
         * @var \Magento\Customer\Model\Attribute
         */
        $lastTokenAttribute = $this->getAttribute();
        if (empty($lastTokenAttribute) || !$lastTokenAttribute->getAttributeId()) {
            return '';
        }
        $select = $this->resourceConnection->getConnection()
            ->select()->from('customer_entity_text', ['value'])
            ->where('entity_id = ?', $customerId)
            ->where('attribute_id = ?', $lastTokenAttribute->getAttributeId());
        $rows = $this->resourceConnection->getConnection()->fetchCol($select);
        if (empty($rows)) {
            return '';
        }
        $tokenData = json_decode($rows[0], true);
        return isset($tokenData['accessToken']) ? $this->hotaiService->encryptToken($tokenData['accessToken']) : '';
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function generateNewToken()
    {
        return $this->random->getRandomString(15);
    }

    /**
     * @return \Magento\Eav\Model\Entity\Attribute\AbstractAttribute|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function getAttribute()
    {
        if ($this->attribute === null) {
            $this->attribute = $this->eavConfig->getAttribute('customer', self::ATTRIBUTE_CODE);
        }
        return $this->attribute;
    }
}
