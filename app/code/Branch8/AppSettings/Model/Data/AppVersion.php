<?php
namespace Branch8\AppSettings\Model\Data;

use Branch8\AppSettings\Api\Data\AppVersionInterface;

class AppVersion implements AppVersionInterface
{
    /**
     * @var \Branch8\AppSettings\Api\Data\VersionInterface
     */
    protected $androidVersion;

    /**
     * @var \Branch8\AppSettings\Api\Data\VersionInterface
     */
    protected $androidMinSupportedVersion;

    /**
     * @var \Branch8\AppSettings\Api\Data\VersionInterface
     */
    protected $iosVersion;

    /**
     * @var \Branch8\AppSettings\Api\Data\VersionInterface
     */
    protected $iosMinSupportedVersion;

    /**
     * @var string | null
     */
    protected $updateMessage;

    public function getAndroidVersion()
    {
        return $this->androidVersion;
    }

    public function setAndroidVersion(\Branch8\AppSettings\Api\Data\VersionInterface $version)
    {
        $this->androidVersion = $version;
        return $this;
    }

    public function getAndroidMinSupportedVersion()
    {
        return $this->androidMinSupportedVersion;
    }

    public function setAndroidMinSupportedVersion(\Branch8\AppSettings\Api\Data\VersionInterface $version)
    {
        $this->androidMinSupportedVersion = $version;
        return $this;
    }

    public function getIosVersion()
    {
        return $this->iosVersion;
    }

    public function setIosVersion(\Branch8\AppSettings\Api\Data\VersionInterface $version)
    {
        $this->iosVersion = $version;
        return $this;
    }

    public function getIosMinSupportedVersion()
    {
        return $this->iosMinSupportedVersion;
    }

    public function setIosMinSupportedVersion(\Branch8\AppSettings\Api\Data\VersionInterface $version)
    {
        $this->iosMinSupportedVersion = $version;
        return $this;
    }


    public function getUpdateMessage()
    {
        return $this->updateMessage;
    }

    public function setUpdateMessage(string $message)
    {
        $this->updateMessage = $message;
        return $this;
    }
}
