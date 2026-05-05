<?php

declare(strict_types=1);

namespace Branch8\HotaiCore\Model\Detection;

use Magento\Framework\HTTP\Header;
use Magento\Framework\ObjectManagerInterface;

class MobileDetect
{
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @var \Detection\MobileDetect|null
     */
    private $mobileDetector = null;

    /**
     * @var Header
     */
    private $httpHeader;

    public function __construct(
        Header $httpHeader,
        ObjectManagerInterface $objectManager
    ) {
        $this->httpHeader = $httpHeader;
        $this->objectManager = $objectManager;

        // We are using object manager to create 3rd-party packages' class
        if (class_exists(\Detection\MobileDetect::class)) {
            $this->mobileDetector = $this->objectManager->create(\Detection\MobileDetect::class);
        }
    }

    public function isMobile(): bool
    {
        return $this->mobileDetector === null
            ? stristr($this->httpHeader->getHttpUserAgent(), 'mobi') !== false
            : $this->mobileDetector->isMobile();
    }

    public function isHotaiApp(): bool
    {
        return stristr($this->httpHeader->getHttpUserAgent(), 'HotaiApp') !== false;
    }

    public function getDeviceUUID(): string
    {
        $deviceUUID = '';
        if ($this->isHotaiApp()) {
            $userAgent = $this->httpHeader->getHttpUserAgent();
            if (preg_match('/HotaiApp\/[\d.]+\/[^\/]+\/([a-f0-9-]{36})/i', $userAgent, $matches)) {
                return $matches[1];
            }

            $parts = explode('/', $userAgent);
            $deviceUUID = end($parts);
        }

        return $deviceUUID;
    }


    public function getDeviceType(): string
    {
        $userAgent = strtolower($this->httpHeader->getHttpUserAgent());

        if ($this->isHotaiApp()) {
            if (stripos($userAgent, 'android') !== false) {
                return 'android';
            } elseif (stripos($userAgent, 'iphone') !== false || stripos($userAgent, 'ipad') !== false) {
                return 'ios';
            } else {
                return 'other';
            }
        }

        $webKeywords = ['iphone', 'ipad', 'android', 'windows', 'macintosh', 'linux'];
        foreach ($webKeywords as $keyword) {
            if (str_contains($userAgent, $keyword)) {
                return 'web';
            }
        }

        return 'other';
    }
}
