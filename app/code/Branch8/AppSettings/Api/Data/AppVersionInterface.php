<?php

namespace Branch8\AppSettings\Api\Data;

interface AppVersionInterface
{

    /**
     * Get Android version info
     * @return \Branch8\AppSettings\Api\Data\VersionInterface
     */
    public function getAndroidVersion();


    /**
     * Set Android version info
     * @param \Branch8\AppSettings\Api\Data\VersionInterface $version
     * @return self
     */
    public function setAndroidVersion(\Branch8\AppSettings\Api\Data\VersionInterface $version);


    /**
     * Get Android version info
     * @return \Branch8\AppSettings\Api\Data\VersionInterface
     */
    public function getAndroidMinSupportedVersion();


    /**
     * Set Android version info
     * @param \Branch8\AppSettings\Api\Data\VersionInterface $version
     * @return self
     */
    public function setAndroidMinSupportedVersion(\Branch8\AppSettings\Api\Data\VersionInterface $version);



    /**
     * Get Android version info
     * @return \Branch8\AppSettings\Api\Data\VersionInterface
     */
    public function getIosVersion();


    /**
     * Set Android version info
     * @param \Branch8\AppSettings\Api\Data\VersionInterface $version
     * @return self
     */
    public function setIosVersion(\Branch8\AppSettings\Api\Data\VersionInterface $version);


    /**
     * Get Android version info
     * @return \Branch8\AppSettings\Api\Data\VersionInterface
     */
    public function getIosMinSupportedVersion();


    /**
     * Set Android version info
     * @param \Branch8\AppSettings\Api\Data\VersionInterface $version
     * @return self
     */
    public function setIosMinSupportedVersion(\Branch8\AppSettings\Api\Data\VersionInterface $version);


    /**
     * Get content
     * @return string
     */
    public function getUpdateMessage();


    /**
     * Set content
     * @param string $message
     * @return self
     */
    public function setUpdateMessage(string $message);
}
