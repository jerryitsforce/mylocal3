<?php
declare(strict_types=1);

namespace Branch8\FamilyBonusPin\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class FtpConfig extends AbstractHelper
{
    const XML_PATH_FTP_STORE_NO = 'family_bonus_pin/ftp/store_no';
    const XML_PATH_FTP_CONNECTION_PATH = 'family_bonus_pin/ftp/connection_path';
    const XML_PATH_FTP_CONNECTION_PORT = 'family_bonus_pin/ftp/connection_port';
    const XML_PATH_FTP_CONNECTION_ACCOUNT = 'family_bonus_pin/ftp/connection_account';
    const XML_PATH_FTP_CONNECTION_PASSWORD = 'family_bonus_pin/ftp/connection_password';

    /**
     * Get FTP store number
     *
     * @param int|null $storeId
     * @return string
     */
    public function getStoreNo(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_FTP_STORE_NO,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get FTP connection path
     *
     * @param int|null $storeId
     * @return string
     */
    public function getConnectionPath(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_FTP_CONNECTION_PATH,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get FTP connection port
     *
     * @param int|null $storeId
     * @return int
     */
    public function getConnectionPort(?int $storeId = null): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_FTP_CONNECTION_PORT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get FTP connection account
     *
     * @param int|null $storeId
     * @return string
     */
    public function getConnectionAccount(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_FTP_CONNECTION_ACCOUNT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get FTP connection password
     *
     * @param int|null $storeId
     * @return string
     */
    public function getConnectionPassword(?int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue(
            self::XML_PATH_FTP_CONNECTION_PASSWORD,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get all FTP configuration as array
     *
     * @param int|null $storeId
     * @return array
     */
    public function getFtpConfig(?int $storeId = null): array
    {
        return [
            'StoreNo' => $this->getStoreNo($storeId),
            'ConnectionPath' => $this->getConnectionPath($storeId),
            'ConnectionPathPort' => $this->getConnectionPort($storeId),
            'ConnectionAccount' => $this->getConnectionAccount($storeId),
            'ConnectionPassword' => $this->getConnectionPassword($storeId),
        ];
    }

    /**
     * Check if FTP configuration is complete
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isFtpConfigured(?int $storeId = null): bool
    {
        return !empty($this->getStoreNo($storeId))
            && !empty($this->getConnectionPath($storeId))
            && !empty($this->getConnectionPort($storeId))
            && !empty($this->getConnectionAccount($storeId));
    }
}
