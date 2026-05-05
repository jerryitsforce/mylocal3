<?php

namespace Branch8\TicketApi\Helper;

use Magento\Framework\Math\Random;

class Common
{
    const MERCHANT_ID_POOL  = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    const MERCHANT_ID_COUNT = 16;

    const AES_KEY_POOL  = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    const AES_KEY_COUNT = 32;

    const AES_IV_POOL  = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    const AES_IV_COUNT = 16;

    /**
     * @var Random
     */
    private Random $random;

    /**
     * @param Random $random
     */
    public function __construct(
        Random $random
    ) {
        $this->random = $random;
    }

    /**
     * 產出隨機Merchant ID
     * @return string
     */
    public function generateMerchantId(): string
    {
        return $this->random->getRandomString(self::MERCHANT_ID_COUNT, self::MERCHANT_ID_POOL);
    }

    /**
     * 產出隨機AES Key
     * @return string
     */
    public function generateAesKey(): string
    {
        return $this->random->getRandomString(self::AES_KEY_COUNT, self::AES_KEY_POOL);
    }

    /**
     * 產出隨機AES IV
     * @return string
     */
    public function generateAesIv(): string
    {
        return $this->random->getRandomString(self::AES_IV_COUNT, self::AES_IV_POOL);
    }
}
