<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Branch8\MarketplaceProduct\Controller\Product\Gallery;

use Branch8\MarketplaceProduct\Model\FileUploaderFactory;
use Branch8\MarketplaceStaging\Model\MediaGalleryUploaderConfig;
use Magento\Framework\Exception\LocalizedException;

/**
 * Marketplace Product Image Upload controller.
 */
class Upload extends \Webkul\Marketplace\Controller\Product\Gallery\Upload
{
    private $allowedMimeTypes = [
        'jpg' => 'image/jpg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/png',
        'png' => 'image/gif'
    ];

    /**
     * Function execute
     */
    public function execute()
    {
        $helper = $this->helper;
        $isPartner = $helper->isSeller();
        $maxUploadSize = $this->_objectManager->get(MediaGalleryUploaderConfig::class)->getMaxUploadSize();
        $filerUploader = $this->_objectManager->get(FileUploaderFactory::class);
        if ($isPartner == 1) {
            try {
                $target = $this->_mediaDirectory->getAbsolutePath(
                    $this->mediaConfig->getBaseTmpMediaPath()
                );
                $fileUploader = $filerUploader->create(
                    ['fileId' => 'image']
                );
                $fileUploader->setAllowedExtensions(
                    ['gif', 'jpg', 'png', 'jpeg']
                );
                if (!$fileUploader->checkMimeType($this->allowedMimeTypes)) {
                    throw new LocalizedException(
                        __('We are unable to recognize or support this file extension type.')
                    );
                }
                $fileUploader->setFilesDispersion(true);
                $fileUploader->setAllowRenameFiles(true);
                $resultData = $fileUploader->save($target, time() . '.' . $fileUploader->getFileExtension());
                unset($resultData['tmp_name']);
                unset($resultData['path']);
                $resultData['url'] = $this->mediaConfig->getTmpMediaUrl($resultData['file']);
                $resultData['file'] = $resultData['file'] . '.tmp';
                $this->getResponse()->representJson(
                    $this->jsonHelper->jsonEncode($resultData)
                );
            } catch (\Exception $e) {
                $this->helper->logDataInLogger(
                    "Controller_Product_Gallery_Upload execute : " . $e->getMessage()
                );
                $this->getResponse()->representJson(
                    $this->jsonHelper->jsonEncode(
                        [
                            'error' => $e->getMessage(),
                            'errorcode' => $e->getCode(),
                        ]
                    )
                );
            }
        } else {
            return $this->resultRedirectFactory->create()->setPath(
                'marketplace/account/becomeseller',
                ['_secure' => $this->getRequest()->isSecure()]
            );
        }
    }
}
