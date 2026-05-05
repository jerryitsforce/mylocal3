<?php
namespace Branch8\AppSettings\Model;

use Branch8\AppSettings\Api\Data\AppVersionInterfaceFactory;
use Branch8\AppSettings\Api\Data\AppVersionInterface;
use Branch8\AppSettings\Api\Data\VersionInterfaceFactory;
use Branch8\AppSettings\Api\Data\VersionInterface;
use Branch8\AppSettings\Api\AppVersionControlInterface;
use Branch8\AppSettings\Helper\Data;

class AppVersionControl implements AppVersionControlInterface
{
    protected Data $helper;
    protected AppVersionInterfaceFactory $responseFactory;
    protected VersionInterfaceFactory $versionFactory;

    public function __construct(
        Data $helper,
        AppVersionInterfaceFactory $responseFactory,
        VersionInterfaceFactory $versionFactory
    ) {
        $this->helper = $helper;
        $this->responseFactory = $responseFactory;
        $this->versionFactory = $versionFactory;
    }

    public function get(): AppVersionInterface
    {
       $response = $this->responseFactory->create();
       $response->setAndroidVersion($this->getVersion('android_version'))
           ->setAndroidMinSupportedVersion($this->getVersion('android_min_supported_version'))
           ->setIosVersion($this->getVersion('ios_version'))
           ->setIosMinSupportedVersion($this->getVersion('ios_min_supported_version'))
           ->setUpdateMessage($this->helper->getUpdateMessage());
       return $response;
    }

    public function getVersion($key): VersionInterface
    {
        switch ($key) {
            case 'android_version':
                $setting = $this->helper->getAndroidVersion();
                break;
            case 'android_min_supported_version':
                $setting = $this->helper->getAndroidMinSupportedVersion();
                break;
            case 'ios_version':
                $setting = $this->helper->getIOSVersion();
                break;
            case 'ios_min_supported_version':
                $setting = $this->helper->getIOSMinSupportedVersion();
                break;
            default:
                throw new \InvalidArgumentException("Unknown version key: $key");
        }

        /** @var VersionInterface $version */
        return $this->versionFactory->create()
                ->setMajor($setting['major'] ?? 0)
                ->setMinor($setting['minor'] ?? 0)
                ->setPatch($setting['patch'] ?? 0)
                ->setBuild($setting['build'] ?? 0);
    }
}
