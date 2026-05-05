<?php
declare(strict_types=1);

namespace HotaiConnected\TicketApi\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Psr\Log\LoggerInterface;

class MerchantResource extends AbstractDb
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @param Context         $context
     * @param LoggerInterface $logger
     * @param string|null     $connectionName
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        ?string $connectionName = null
    ) {
        parent::__construct($context, $connectionName);
        $this->logger = $logger;
    }

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('ticket_api_merchant', 'merchant_id');
    }

    /**
     * Get merchant ID by merchant name
     *
     * @param  string $merchantName
     * @return string|null
     */
    public function getMerchantId(string $merchantName): ?string
    {
        try {
            $connection = $this->getConnection();
            
            $select = $connection->select()
                ->from($this->getMainTable(), ['merchant_id'])
                ->where('merchant_name = ?', $merchantName);
            
            $result = $connection->fetchOne($select);
            
            return $result ?: null;
        } catch (\Exception $e) {
            $this->logger->error('[family_notify] Error fetching merchant ID: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Decrypt data using merchant's encryption keys
     *
     * @param  string $encryptedData The data to decrypt
     * @param  string $merchantName  The merchant name to get encryption keys
     * @return string|null Returns decrypted data or null if decryption fails
     */
    public function decryptMerchantData(string $encryptedData, string $merchantName): ?string
    {
        try {
            // 從資料庫獲取加密金鑰
            $connection = $this->getConnection();
            
            $select = $connection->select()
                ->from($this->getMainTable(), ['aes_key', 'aes_iv'])
                ->where('merchant_name = ?', $merchantName);
            
            $result = $connection->fetchRow($select);
            
            if (!$result) {
                $this->logger->error('[family_notify] Encryption keys not found for merchant: ' . $merchantName);
                return null;
            }

            $sourceKey = $result['aes_key'];
            $sourceIv = $result['aes_iv'];

            $combinedData = base64_decode($encryptedData);
        
            // 準備key
            $hashedKey = hash('sha256', $sourceKey, true); // true 表示返回原始二進制數據
        
            // 準備iv
            $hashedIv = hash('sha256', $sourceIv, true);
            $leftIv = substr($hashedIv, 0, 16); // 取前 16 bytes
        
            // 解密
            $decrypted = openssl_decrypt(
                $combinedData,
                'AES-256-CBC',    // 使用 AES-256-CBC 模式
                $hashedKey,       // 使用 SHA256 處理過的 key
                OPENSSL_RAW_DATA, // 使用原始數據模式
                $leftIv           // 使用處理過的 IV
            );

            if ($decrypted === false) {
                $this->logger->error('[family_notify] Decryption failed for merchant: ' . $merchantName);
                return null;
            }

            return $decrypted;
        } catch (\Exception $e) {
            $this->logger->error('[family_notify] Error in decryption process: ' . $e->getMessage());
            return null;
        }
    }
}
