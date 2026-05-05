<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Branch8\BrandManagement\Plugin\MediaGallery\OnInsert;

use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\UrlInterface;
use Magento\MediaGalleryUi\Controller\Adminhtml\Image\OnInsert;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Plugin to add url field to oninsert response for WYSIWYG editor compatibility
 */
class AddUrlToResponse
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    private LoggerInterface $logger;

    /**
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     */
    public function __construct(StoreManagerInterface $storeManager, LoggerInterface $logger)
    {
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    /**
     * Add url field to response data
     *
     * @param OnInsert $subject
     * @param \Closure $proceed
     * @return ResultInterface
     */
    public function aroundExecute(OnInsert $subject, \Closure $proceed): ResultInterface
    {
        $result = $proceed();
        
        // Use reflection to get and modify the JSON data
        try {
            $reflection = new \ReflectionClass($result);
            $jsonProperty = $reflection->getProperty('json');
            $jsonProperty->setAccessible(true);
            $jsonString = $jsonProperty->getValue($result);
            
            if ($jsonString) {
                $serializer = \Magento\Framework\App\ObjectManager::getInstance()
                    ->get(\Magento\Framework\Serialize\Serializer\Json::class);
                $data = $serializer->unserialize($jsonString);
                
                // Extract actual image URL from directive URL
                if (isset($data['content'])) {
                    $actualUrl = $this->extractImageUrlFromDirective($data['content']);
                    $originalContent = $data['content'];
                    
                    if ($actualUrl) {
                        // Add url field - WYSIWYG editor expects this field
                        if (!isset($data['url'])) {
                            $data['url'] = $actualUrl;
                        }
                        
                        // For TinyMCE callback, content should be the actual image URL (not HTML tag)
                        // TinyMCE will automatically create <img> tag when meta.filetype is 'image'
                        $data['content'] = $actualUrl;
                    } elseif (!isset($data['url'])) {
                        // Fallback: use content as url if extraction fails
                        $data['url'] = $originalContent;
                    }
                }
                
                // Update the JSON string
                $jsonProperty->setValue($result, $serializer->serialize($data));
            }
        } catch (\Exception $e) {
            $this->logger->error($e);
            // If reflection fails, silently continue
        }
        
        return $result;
    }
    
    /**
     * Extract actual image URL from directive URL
     *
     * @param string $directiveUrl
     * @return string|null
     */
    private function extractImageUrlFromDirective(string $directiveUrl): ?string
    {
        // Match directive pattern: /admin/cms/wysiwyg/directive/___directive/{base64}/key/...
        if (preg_match('/directive\/___directive\/([^\/]+)/', $directiveUrl, $matches)) {
            try {
                // Decode base64 (with URL-safe character replacement)
                // Magento uses URL-safe base64 encoding: - instead of +, _ instead of /
                $encoded = strtr($matches[1], '-_,', '+/=');
                $decoded = base64_decode($encoded, true);
                
                if ($decoded && preg_match('/\{\{media\s+url=["\']?([^"\'}\s]+)["\']?\}\}/', $decoded, $urlMatches)) {
                    $mediaPath = trim($urlMatches[1], '"\'');
                    
                    // Keep the leading dot if present (e.g., ".renditions/wysiwyg/pic_1.png")
                    // Only remove leading slashes
                    $mediaPath = ltrim($mediaPath, '/');
                    
                    // Get base media URL
                    $baseMediaUrl = $this->storeManager->getStore()
                        ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
                    
                    // Ensure base URL contains /pub/media
                    // If base URL contains /media but not /pub/media, replace it
                    if (strpos($baseMediaUrl, '/pub/media') === false && strpos($baseMediaUrl, '/media') !== false) {
                        // Replace /media with /pub/media
                        $baseMediaUrl = str_replace('/media', '/pub/media', $baseMediaUrl);
                    } elseif (strpos($baseMediaUrl, '/pub/media') === false) {
                        // If /media is not found, append /pub/media
                        $baseUrl = rtrim($this->storeManager->getStore()
                            ->getBaseUrl(UrlInterface::URL_TYPE_WEB), '/');
                        $baseMediaUrl = $baseUrl . '/pub/media';
                    }
                    
                    // Construct full URL
                    return rtrim($baseMediaUrl, '/') . '/' . $mediaPath;
                }
            } catch (\Exception $e) {
                // If decoding fails, return null
            }
        }
        
        return null;
    }
}

