<?php
namespace Branch8\AppSettings\Observer;

use Branch8\AppSettings\Helper\Data;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Branch8\AppSettings\Model\Service\FirestoreClient;

class VersioningChanged implements ObserverInterface
{
    private const XML_PATH_PREFIX = 'app_settings/versioning/';

    public function __construct(
        private Data $helper,
        private FirestoreClient $firestoreClient,
        private LoggerInterface $logger
    ) {}

    public function execute(Observer $observer)
    {
        try {
            $env = $this->helper->getFirebaseEnvironment();
            $payload = [
                'android_min_supported_version' => $this->getVersion('android_min_supported_version'),
                'android_version'               => $this->getVersion('android_version'),
                'ios_min_supported_version'     => $this->getVersion('ios_min_supported_version'),
                'ios_version'                   => $this->getVersion('ios_version'),
                'update_message'                => $this->helper->getUpdateMessage()
            ];

            $this->firestoreClient->upsertVersionConfig($env, $payload);
        } catch (\Throwable $e) {
            $this->logger->error('[AppVersioning] Firestore update failed: '.$e->getMessage());
        }
    }



    public function getVersion($key): array
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

        return $setting;
    }
}
