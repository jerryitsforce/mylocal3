<?php
declare(strict_types=1);

namespace HotaiConnected\FinancialReconciliation\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Session\SaveHandlerInterface;

class SessionEncryptor extends AbstractHelper
{
    const XML_PATH_SESSION_SALT = 'financial_reconciliation/general/session_salt';
    const ENCRYPTION_ALGORITHM = 'aes-256-cbc';
    const ENCRYPTION_SECRET_KEY_PHRASE = 'HotaiFinancialReconciliationSecret'; // This should be unique and kept secure

    protected $scopeConfig;
    protected $saveHandler;

    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        SaveHandlerInterface $saveHandler
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->saveHandler = $saveHandler;
    }

    /**
     * Get the encryption key derived from salt and a secret phrase.
     *
     * @return string
     */
    private function getEncryptionKey(): string
    {
        $salt = $this->scopeConfig->getValue(
            self::XML_PATH_SESSION_SALT,
            ScopeInterface::SCOPE_STORE
        );

        if (empty($salt)) {
            // Fallback or throw an exception if salt is not configured
            // For now, using a default for development, but it should be configured securely.
            $salt = 'default_secure_salt_please_configure';
        }

        // Derive a consistent key using a strong hashing function
        return hash_pbkdf2('sha256', self::ENCRYPTION_SECRET_KEY_PHRASE, $salt, 10000, 32, true); // 32 bytes for AES-256
    }

    /**
     * Encrypts a session ID.
     *
     * @param string $sessionId
     * @return string|false
     */
    public function encrypt(string $sessionId): string|false
    {
        $key = $this->getEncryptionKey();
        $iv_length = openssl_cipher_iv_length(self::ENCRYPTION_ALGORITHM);
        $iv = openssl_random_pseudo_bytes($iv_length);

        $encrypted = openssl_encrypt($sessionId, self::ENCRYPTION_ALGORITHM, $key, 0, $iv);

        if ($encrypted === false) {
            return false; // Encryption failed
        }

        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypts an encrypted session ID.
     *
     * @param string $encryptedSessionId
     * @return string|false
     */
    public function decrypt(string $encryptedSessionId): string|false
    {
        $key = $this->getEncryptionKey();
        $data = base64_decode($encryptedSessionId);
        if ($data === false) {
            return false; // Base64 decode failed
        }

        $iv_length = openssl_cipher_iv_length(self::ENCRYPTION_ALGORITHM);
        if (strlen($data) < $iv_length) {
            return false; // Data is too short, possibly corrupted or not encrypted correctly
        }

        $iv = substr($data, 0, $iv_length);
        $encrypted = substr($data, $iv_length);

        $decrypted = openssl_decrypt($encrypted, self::ENCRYPTION_ALGORITHM, $key, 0, $iv);

        return $decrypted;
    }

    public function decodeMPSessionById(string $sessionId): string|null
    {
        $data = $this->saveHandler->read($sessionId);
        if ($data === false || $data === '') {
            return null;
        }

        $data = trim($data);

        if (preg_match('/"customer_id";s:\d+:"(\d+)"/', $data, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public function decodeSessionById(string $sessionId): string|null
    {
        $data = $this->saveHandler->read($sessionId);
        $this->_logger->info(__CLASS__ . " Session ID: $sessionId, Raw Data: " . json_encode($data));

        if ($data === false || $data === '') {
            return null;
        }

        $data = trim($data);

        if (preg_match('/"user_id";s:\d+:"(\d+)";s:9:"firstname"/', $data, $matches)) {
            $this->_logger->info(__CLASS__ . " Extracted User ID: " . $matches[1]);
            return $matches[1];
        }

        // 若不是 JSON，則使用 Magento/PHP Session 格式解析
        return $this->decodeMagentoSession($data);
    }

    /**
     * 從 session_id 取得 userId
     */
    public function getUserIdFromSessionId(string $sessionId): string|null
    {
        $userId = $this->decodeSessionById($sessionId);
        return $userId;
    }

    /**
     * 從 session_id 取得 admin 或 _origData（常用）資訊
     */
    public function getAdminFromSessionId(string $sessionId): ?array
    {
        $sess = $this->decodeSessionById($sessionId);
        if (isset($sess['admin']) && is_array($sess['admin']) && !empty($sess['admin'])) {
            return $sess['admin'];
        }
        if (isset($sess['_origData']) && is_array($sess['_origData']) && !empty($sess['_origData'])) {
            return $sess['_origData'];
        }

        return null;
    }
}
