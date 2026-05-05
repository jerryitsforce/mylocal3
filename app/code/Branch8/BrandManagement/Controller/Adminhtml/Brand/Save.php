<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Branch8\BrandManagement\Controller\Adminhtml\Brand;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Branch8\BrandManagement\Controller\Adminhtml\Brand;
use Amasty\ShopbyBase\Model\OptionSettingRepository;
use Amasty\ShopbyBase\Model\OptionSettings\Save as OptionSettingsSave;
use Amasty\ShopbyBase\Model\OptionSettings\ImageFileResolver;
use Amasty\ShopbyBase\Helper\FilterSetting;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;

/**
 * Brand Save Controller
 */
class Save extends Brand
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    /**
     * @var OptionSettingRepository
     */
    protected $optionSettingRepository;

    /**
     * @var OptionSettingsSave
     */
    protected $optionSettingsSave;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var ImageFileResolver
     */
    protected $imageFileResolver;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Constructor
     *
     * @param Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Branch8\BrandManagement\Api\BrandOptionRepositoryInterface $brandOptionRepository
     * @param OptionSettingRepository $optionSettingRepository
     * @param OptionSettingsSave $optionSettingsSave
     * @param ResourceConnection $resourceConnection
     * @param ImageFileResolver $imageFileResolver
     * @param Filesystem $filesystem
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Branch8\BrandManagement\Api\BrandOptionRepositoryInterface $brandOptionRepository,
        OptionSettingRepository $optionSettingRepository,
        OptionSettingsSave $optionSettingsSave,
        ResourceConnection $resourceConnection,
        ImageFileResolver $imageFileResolver,
        Filesystem $filesystem,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger
    ) {
        $this->optionSettingRepository = $optionSettingRepository;
        $this->optionSettingsSave = $optionSettingsSave;
        $this->resourceConnection = $resourceConnection;
        $this->imageFileResolver = $imageFileResolver;
        $this->filesystem = $filesystem;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        parent::__construct($context, $coreRegistry, $brandOptionRepository);
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getParams();

        if ($data) {
            // Support both 'id' (existing) and 'option_id' (Amasty style) parameters
            $optionId = (int) ($this->getRequest()->getParam('option_id') ?: $this->getRequest()->getParam('id') ?: ($data['option_id'] ?? null));
            $attributeCode = $this->getRequest()->getParam('attribute_code') ?: 'brand';
            $storeId = (int) $this->getRequest()->getParam('store', Store::DEFAULT_STORE_ID);
            $savedOptionId = $optionId;

            try {
                if ($optionId) {
                    // Update existing brand
                    $this->saveBrandOption($optionId, $attributeCode, $storeId, $data);
                    $this->messageManager->addSuccessMessage(__('Brand has been updated.'));
                } else {
                    // Create new brand
                    $newOptionId = $this->brandOptionRepository->createBrandOption($data['value'], $data['sort_order'] ?? 0);
                    $savedOptionId = $newOptionId;
                    $this->saveBrandOption($newOptionId, $attributeCode, $storeId, $data);
                    $this->messageManager->addSuccessMessage(__('Brand has been created.'));
                }
            } catch (\Exception $e) {
                $this->logger->error($e);
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the brand.'));
                // If there's an error and we have an ID, redirect back to edit page
                if ($optionId) {
                    return $resultRedirect->setPath('*/*/edit', ['id' => $optionId, 'store' => $storeId]);
                }
                return $resultRedirect->setPath('*/*/new');
            }

            // Check if 'Save and Continue Edit' was clicked
            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', [
                    'id' => $savedOptionId,
                    'store' => $storeId
                ]);
            }
        }

        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Save brand option using Amasty's save logic
     *
     * @param int $optionId
     * @param string $attributeCode
     * @param int $storeId
     * @param array $data
     * @return void
     * @throws \Exception
     */
    private function saveBrandOption(int $optionId, string $attributeCode, int $storeId, array $data)
    {
        if ($storeId !== Store::DEFAULT_STORE_ID) {
            $this->ensureDefaultStoreOptionExists($attributeCode, $optionId);
        }

        // Update brand option basic information (value and sort_order)
        if (isset($data['value']) || isset($data['sort_order'])) {
            $this->brandOptionRepository->updateBrandOption(
                $optionId,
                $data['value'] ?? null,
                $data['sort_order'] ?? null
            );
        }
        // Prepare data for Amasty OptionSettings Save service
        // Remove fields that are not part of OptionSetting
        $optionSettingData = $data;
        unset($optionSettingData['id'], $optionSettingData['option_id'], $optionSettingData['value'], $optionSettingData['sort_order']);

        // Align with Amasty OptionSettings\Save: use_default is a list of field names; processUseDefault sets those fields to null (inherit store 0). UI sends assoc [field => 1]; legacy form sends use_default[].
        $this->normalizeUseDefaultPayloadForAmasty($optionSettingData);

        // Handle image data from UI component format
        $this->prepareImageData($optionSettingData);

        // Handle WYSIWYG editor images in description field
        if (isset($optionSettingData['description'])) {
            $optionSettingData['description'] = $this->processWysiwygImages($optionSettingData['description']);
        }

        // Handle CMS block IDs - convert empty strings to null
        if (isset($optionSettingData['top_cms_block_id']) && $optionSettingData['top_cms_block_id'] === '') {
            $optionSettingData['top_cms_block_id'] = null;
        }
        if (isset($optionSettingData['bottom_cms_block_id']) && $optionSettingData['bottom_cms_block_id'] === '') {
            $optionSettingData['bottom_cms_block_id'] = null;
        }

        // Preserve image values before Amasty processes them
        // Amasty's resolveImageUpload might override them if $_FILES is empty
        $preservedImage = $optionSettingData['image'] ?? null;
        $preservedSliderImage = $optionSettingData['slider_image'] ?? null;


        // Use Amasty's OptionSettings Save service (same as Amasty slider/edit)
        $result = $this->optionSettingsSave->saveData($attributeCode, $optionId, $storeId, $optionSettingData);

        // If Amasty cleared the image fields but we had valid values, restore them
        if ($preservedImage && empty($result->getImage())) {
            $result->setImage($preservedImage);
            $this->optionSettingRepository->save($result);
        }
        if ($preservedSliderImage && empty($result->getSliderImage())) {
            $result->setSliderImage($preservedSliderImage);
            $this->optionSettingRepository->save($result);
        }
    }

    /**
     * Ensure default store (0) option setting row exists before saving a store view (matches Amasty Option\Save).
     */
    private function ensureDefaultStoreOptionExists(string $attributeCode, int $optionId): void
    {
        $setting = $this->optionSettingRepository->getByCode($attributeCode, $optionId, Store::DEFAULT_STORE_ID);
        if (!$setting->getId()) {
            $this->optionSettingsSave->saveData($attributeCode, $optionId, Store::DEFAULT_STORE_ID, []);
        }
    }

    /**
     * Normalize use_default to Amasty format (indexed list of field names). Strip image fields — brand UI does not support Use Default for images.
     *
     * @param array $data
     * @return void
     */
    private function normalizeUseDefaultPayloadForAmasty(array &$data): void
    {
        if (!isset($data['use_default']) || !is_array($data['use_default'])) {
            unset($data['use_default']);
            return;
        }

        $skipFields = ['image', 'slider_image'];
        $fieldNames = [];
        foreach ($data['use_default'] as $key => $value) {
            $isListFormat = is_int($key) || (is_string($key) && ctype_digit((string) $key));
            if ($isListFormat) {
                if (is_string($value) && $value !== '' && !in_array($value, $skipFields, true)) {
                    $fieldNames[] = $value;
                }
            } elseif ($value) {
                if (is_string($key) && $key !== '' && !in_array($key, $skipFields, true)) {
                    $fieldNames[] = $key;
                }
            }
        }

        $data['use_default'] = array_values(array_unique($fieldNames));
    }

    /**
     * Prepare image data from UI component format to Amasty format
     *
     * @param array &$data
     * @return void
     */
    private function prepareImageData(array &$data): void
    {
        $mediaDirectory = $this->filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        $tmpPath = 'tmp/brandmanagement';
        // Amasty IMAGES_DIR is '/amasty/shopby/option_images/' - normalize path
        $finalPath = trim(\Amasty\ShopbyBase\Model\OptionSetting::IMAGES_DIR, '/');
        $sliderPath = $finalPath . '/' . \Amasty\ShopbyBase\Model\OptionSetting::SLIDER_DIR;

        // Handle image field
        if (isset($data['image'])) {
            if (is_array($data['image'])) {
                // UI component format: [['file' => 'filename.jpg']]
                $isRemoved = false;
                $tmpFile = null;

                if (count($data['image']) === 0) {
                    // Empty array means image was removed
                    $isRemoved = true;
                } elseif (isset($data['image'][0])) {
                    $firstElement = $data['image'][0];
                    // Check for isRemoved flag (set by UI component when preview-image is deleted)
                    if (isset($firstElement['isRemoved']) && $firstElement['isRemoved']) {
                        $isRemoved = true;
                    } elseif (isset($firstElement['file']) && !empty(trim($firstElement['file']))) {
                        $tmpFile = trim($firstElement['file']);
                        
                        // Check if file exists in tmp or final location
                        $tmpFilePath = $tmpPath . '/' . $tmpFile;
                        $finalFilePath = $finalPath . '/' . $tmpFile;
                        
                        $fileInTmp = $mediaDirectory->isFile($tmpFilePath);
                        $fileInFinal = $mediaDirectory->isFile($finalFilePath);
                        
                        if ($fileInTmp && !$fileInFinal) {
                            // File is in tmp, move to final
                            $mediaDirectory->create($finalPath);
                            $mediaDirectory->copyFile($tmpFilePath, $finalFilePath);
                            $mediaDirectory->delete($tmpFilePath);
                            $data['image'] = $tmpFile;
                        } elseif ($fileInFinal) {
                            // File already in final location
                            $data['image'] = $tmpFile;
                        } else {
                            // File doesn't exist - might be handled by Amasty's upload handler
                            // But set it anyway so Amasty can process it
                            $data['image'] = $tmpFile;
                        }
                    } elseif (isset($firstElement['url']) && !empty($firstElement['url'])) {
                        // If file is missing but url exists, extract filename and path from URL
                        // This handles images added via WYSIWYG or already existing images
                        $url = $firstElement['url'];
                        /** @var \Magento\Store\Model\Store $store */
                        $store = $this->storeManager->getStore();
                        $baseMediaUrl = rtrim($store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA), '/') . '/';
                        
                        // Extract relative path from URL
                        $relativePath = $url;
                        if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
                            // Full URL - extract relative path
                            if (strpos($url, $baseMediaUrl) !== false) {
                                $relativePath = str_replace($baseMediaUrl, '', $url);
                            } else {
                                // External URL, can't process - treat as removed
                                $isRemoved = true;
                            }
                        }
                        
                        if (!$isRemoved) {
                            $relativePath = ltrim($relativePath, '/');
                            
                            // Remove 'media/' prefix if present (mediaDirectory expects path relative to media dir)
                            if (strpos($relativePath, 'media/') === 0) {
                                $relativePath = substr($relativePath, 6); // Remove 'media/' (6 characters)
                            }
                            
                            // Remove query parameters from relative path
                            $relativePath = preg_replace('/\?.*$/', '', $relativePath);
                            
                            // Extract filename
                            $tmpFile = basename($relativePath);
                            // Remove query parameters if any (additional safety)
                            $tmpFile = preg_replace('/\?.*$/', '', $tmpFile);
                            
                            // Check if file exists in the location specified by URL
                            $sourceFilePath = $relativePath;
                            $tmpFilePath = $tmpPath . '/' . $tmpFile;
                            $finalFilePath = $finalPath . '/' . $tmpFile;
                            
                            // Use isFile() to check for files specifically
                            $fileInSource = $mediaDirectory->isFile($sourceFilePath);
                            $fileInTmp = $mediaDirectory->isFile($tmpFilePath);
                            $fileInFinal = $mediaDirectory->isFile($finalFilePath);
                            
                            if ($fileInSource && !$fileInFinal) {
                                // File exists in source location (e.g., .renditions/wysiwyg/) but not in final
                                // Copy it to final location
                                $mediaDirectory->create($finalPath);
                                $mediaDirectory->copyFile($sourceFilePath, $finalFilePath);
                                $data['image'] = $tmpFile;
                            } elseif ($fileInFinal) {
                                // File already in final location
                                $data['image'] = $tmpFile;
                            } elseif ($fileInTmp && !$fileInFinal) {
                                // File is in tmp, move to final
                                $mediaDirectory->create($finalPath);
                                $mediaDirectory->copyFile($tmpFilePath, $finalFilePath);
                                $mediaDirectory->delete($tmpFilePath);
                                $data['image'] = $tmpFile;
                            } elseif (!$fileInSource && !$fileInTmp && !$fileInFinal) {
                                // File doesn't exist anywhere - this shouldn't happen
                                // But set it anyway to preserve the filename
                                $data['image'] = $tmpFile;
                            } else {
                                // File in final location
                                $data['image'] = $tmpFile;
                            }
                        }
                    } else {
                        // File key is missing or empty
                        $isRemoved = true;
                    }
                }

                if ($isRemoved) {
                    // Image was removed - set delete flag
                    $data[OptionSettingsSave::IMAGE_DELETE] = true;
                    unset($data['image']);
                }
            } elseif (is_string($data['image'])) {
                $imageValue = trim($data['image']);

                if (empty($imageValue)) {
                    // Empty string means image was removed
                    $data[OptionSettingsSave::IMAGE_DELETE] = true;
                    unset($data['image']);
                } elseif (strpos($imageValue, 'tmp/brandmanagement') !== false) {
                    // Temporary file path, move to final directory
                    $tmpFile = basename($imageValue);
                    $tmpFilePath = $tmpPath . '/' . $tmpFile;
                    $finalFilePath = $finalPath . '/' . $tmpFile;

                    if ($mediaDirectory->isExist($tmpFilePath)) {
                        $mediaDirectory->create($finalPath);
                        $mediaDirectory->copyFile($tmpFilePath, $finalFilePath);
                        $mediaDirectory->delete($tmpFilePath);
                    }
                    $data['image'] = $tmpFile;
                }
                // If it's already a valid filename (not a path), keep it as is
            }
        }

        // Handle slider_image field
        if (isset($data['slider_image'])) {
            if (is_array($data['slider_image'])) {
                // UI component format: [['file' => 'filename.jpg']]
                $isRemoved = false;
                $tmpFile = null;

                if (count($data['slider_image']) === 0) {
                    // Empty array means image was removed
                    $isRemoved = true;
                } elseif (isset($data['slider_image'][0])) {
                    $firstElement = $data['slider_image'][0];
                    // Check for isRemoved flag (set by UI component when preview-image is deleted)
                    if (isset($firstElement['isRemoved']) && $firstElement['isRemoved']) {
                        $isRemoved = true;
                    } elseif (isset($firstElement['file']) && !empty(trim($firstElement['file']))) {
                        $tmpFile = trim($firstElement['file']);
                        
                        // Check if file exists in tmp or final location
                        $tmpFilePath = $tmpPath . '/' . $tmpFile;
                        $finalFilePath = $sliderPath . '/' . $tmpFile;
                        
                        $fileInTmp = $mediaDirectory->isFile($tmpFilePath);
                        $fileInFinal = $mediaDirectory->isFile($finalFilePath);
                        
                        if ($fileInTmp && !$fileInFinal) {
                            // File is in tmp, move to final
                            $mediaDirectory->create($sliderPath);
                            $mediaDirectory->copyFile($tmpFilePath, $finalFilePath);
                            $mediaDirectory->delete($tmpFilePath);
                            $data['slider_image'] = $tmpFile;
                        } elseif ($fileInFinal) {
                            // File already in final location
                            $data['slider_image'] = $tmpFile;
                        } else {
                            // File doesn't exist - might be handled by Amasty's upload handler
                            // But set it anyway so Amasty can process it
                            $data['slider_image'] = $tmpFile;
                        }
                    } elseif (isset($firstElement['url']) && !empty($firstElement['url'])) {
                        // If file is missing but url exists, extract filename and path from URL
                        // This handles images added via WYSIWYG or already existing images
                        $url = $firstElement['url'];
                        /** @var \Magento\Store\Model\Store $store */
                        $store = $this->storeManager->getStore();
                        $baseMediaUrl = rtrim($store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA), '/') . '/';
                        
                        // Extract relative path from URL
                        $relativePath = $url;
                        if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
                            // Full URL - extract relative path
                            if (strpos($url, $baseMediaUrl) !== false) {
                                $relativePath = str_replace($baseMediaUrl, '', $url);
                            } else {
                                // External URL, can't process - treat as removed
                                $isRemoved = true;
                            }
                        }
                        
                        if (!$isRemoved) {
                            $relativePath = ltrim($relativePath, '/');
                            
                            // Remove 'media/' prefix if present (mediaDirectory expects path relative to media dir)
                            if (strpos($relativePath, 'media/') === 0) {
                                $relativePath = substr($relativePath, 6); // Remove 'media/' (6 characters)
                            }
                            
                            // Remove query parameters from relative path
                            $relativePath = preg_replace('/\?.*$/', '', $relativePath);
                            
                            // Extract filename
                            $tmpFile = basename($relativePath);
                            // Remove query parameters if any (additional safety)
                            $tmpFile = preg_replace('/\?.*$/', '', $tmpFile);
                            
                            // Check if file exists in the location specified by URL
                            $sourceFilePath = $relativePath;
                            $tmpFilePath = $tmpPath . '/' . $tmpFile;
                            $finalFilePath = $sliderPath . '/' . $tmpFile;
                            
                            // Use isFile() to check for files specifically
                            $fileInSource = $mediaDirectory->isFile($sourceFilePath);
                            $fileInTmp = $mediaDirectory->isFile($tmpFilePath);
                            $fileInFinal = $mediaDirectory->isFile($finalFilePath);
                            
                            if ($fileInSource && !$fileInFinal) {
                                // File exists in source location (e.g., .renditions/wysiwyg/) but not in final
                                // Copy it to final location
                                $mediaDirectory->create($sliderPath);
                                $mediaDirectory->copyFile($sourceFilePath, $finalFilePath);
                                $data['slider_image'] = $tmpFile;
                            } elseif ($fileInFinal) {
                                // File already in final location
                                $data['slider_image'] = $tmpFile;
                            } elseif ($fileInTmp && !$fileInFinal) {
                                // File is in tmp, move to final
                                $mediaDirectory->create($sliderPath);
                                $mediaDirectory->copyFile($tmpFilePath, $finalFilePath);
                                $mediaDirectory->delete($tmpFilePath);
                                $data['slider_image'] = $tmpFile;
                            } elseif (!$fileInSource && !$fileInTmp && !$fileInFinal) {
                                // File doesn't exist anywhere - this shouldn't happen
                                // But set it anyway to preserve the filename
                                $data['slider_image'] = $tmpFile;
                            } else {
                                // File in final location
                                $data['slider_image'] = $tmpFile;
                            }
                        }
                    } else {
                        // File key is missing or empty
                        $isRemoved = true;
                    }
                }

                if ($isRemoved) {
                    // Image was removed - set delete flag
                    $data[OptionSettingsSave::SLIDER_IMAGE_DELETE] = true;
                    unset($data['slider_image']);
                }
            } elseif (is_string($data['slider_image'])) {
                $imageValue = trim($data['slider_image']);

                if (empty($imageValue)) {
                    // Empty string means image was removed
                    $data[OptionSettingsSave::SLIDER_IMAGE_DELETE] = true;
                    unset($data['slider_image']);
                } elseif (strpos($imageValue, 'tmp/brandmanagement') !== false) {
                    // Temporary file path, move to final directory
                    $tmpFile = basename($imageValue);
                    $tmpFilePath = $tmpPath . '/' . $tmpFile;
                    $finalFilePath = $sliderPath . '/' . $tmpFile;

                    if ($mediaDirectory->isExist($tmpFilePath)) {
                        $mediaDirectory->create($sliderPath);
                        $mediaDirectory->copyFile($tmpFilePath, $finalFilePath);
                        $mediaDirectory->delete($tmpFilePath);
                    }
                    $data['slider_image'] = $tmpFile;
                }
                // If it's already a valid filename (not a path), keep it as is
            }
        }

        // Handle image_delete and slider_image_delete flags
        if (isset($data['image_delete']) && $data['image_delete']) {
            $data[OptionSettingsSave::IMAGE_DELETE] = true;
            unset($data['image_delete']);
        }
        if (isset($data['slider_image_delete']) && $data['slider_image_delete']) {
            $data[OptionSettingsSave::SLIDER_IMAGE_DELETE] = true;
            unset($data['slider_image_delete']);
        }
    }

    /**
     * Process WYSIWYG editor images - move from tmp directory to permanent location
     *
     * @param string $content
     * @return string
     */
    private function processWysiwygImages(string $content): string
    {
        if (empty($content)) {
            return $content;
        }

        $mediaDirectory = $this->filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->storeManager->getStore();
        $baseMediaUrl = rtrim($store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA), '/') . '/';

        // Pattern to match image src attributes (including single quotes)
        preg_match_all('/src=["\']([^"\']*)["\']/', $content, $matches);

        if (empty($matches[1])) {
            return $content;
        }

        $tmpPaths = ['tmp/wysiwyg', 'tmp/desc'];
        $permanentPath = 'wysiwyg/brandmanagement';

        foreach ($matches[1] as $imageUrl) {
            $originalUrl = $imageUrl;

            // Skip if already in permanent location
            if (strpos($imageUrl, $permanentPath) !== false) {
                continue;
            }

            // Extract relative path from URL
            $relativePath = $imageUrl;

            // If it's a full URL, extract the relative path
            if (strpos($imageUrl, 'http://') === 0 || strpos($imageUrl, 'https://') === 0) {
                // Skip external URLs that don't belong to our media
                if (strpos($imageUrl, $baseMediaUrl) === false) {
                    continue; // External URL, skip
                }
                $relativePath = str_replace($baseMediaUrl, '', $imageUrl);
            }

            $relativePath = ltrim($relativePath, '/');

            // Check if image is in tmp directory
            $isTmpImage = false;
            $tmpFilePath = null;

            foreach ($tmpPaths as $tmpPath) {
                if (strpos($relativePath, $tmpPath) === 0) {
                    $isTmpImage = true;
                    $tmpFilePath = $relativePath;
                    break;
                }
            }

            if (!$isTmpImage || !$tmpFilePath) {
                continue;
            }

            // Remove .tmp extension if present
            $tmpFilePath = str_replace('.tmp', '', $tmpFilePath);

            // Check if file exists in tmp directory
            if (!$mediaDirectory->isExist($tmpFilePath)) {
                continue;
            }

            // Generate permanent file path with unique filename
            $fileName = basename($tmpFilePath);
            // Add timestamp to make filename unique
            $pathInfo = pathinfo($fileName);
            $uniqueFileName = $pathInfo['filename'] . '_' . time() . '_' . rand(1000, 9999);
            if (isset($pathInfo['extension'])) {
                $uniqueFileName .= '.' . $pathInfo['extension'];
            }
            $filePath = $permanentPath . '/' . $uniqueFileName;

            // Create permanent directory if it doesn't exist
            if (!$mediaDirectory->isExist($permanentPath)) {
                $mediaDirectory->create($permanentPath);
            }

            // Copy file from tmp to permanent location
            try {
                $mediaDirectory->copyFile($tmpFilePath, $filePath);

                // Update URL in content (handle both single and double quotes)
                $newUrl = $baseMediaUrl . $filePath;
                // Replace with proper quote matching
                $content = preg_replace(
                    '/src=["\']' . preg_quote($originalUrl, '/') . '["\']/',
                    'src="' . $newUrl . '"',
                    $content
                );
            } catch (\Exception $e) {
                // Log error but continue processing
                continue;
            }
        }

        return $content;
    }
}
