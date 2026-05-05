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

namespace Branch8\MarketplaceProduct\Controller\Wysiwyg\Gallery;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Json\Helper\Data;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Webkul\Marketplace\Api\Data\WysiwygImageInterfaceFactory;
use Webkul\Marketplace\Helper\Data as MpHelper;

/**
 * Marketplace Wysiwyg Image Upload controller.
 */
class Upload extends \Webkul\Marketplace\Controller\Wysiwyg\Gallery\Upload
{
    private $allowedMimeTypes = [
        'jpg' => 'image/jpg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/png',
        'png' => 'image/gif'
    ];

    /**
     * @var Filesystem\Driver\File $file
     */
    private $file;

    /**
     * Initialization
     *
     * @param Context $context
     * @param Filesystem $filesystem
     * @param File $file
     * @param UploaderFactory $fileUploaderFactory
     * @param StoreManagerInterface $storeManager
     * @param WysiwygImageInterfaceFactory $wysiwygImage
     * @param MpHelper $mpHelper
     * @param Data $jsonHelper
     * @throws FileSystemException
     */
    public function __construct(
        Context $context,
        Filesystem $filesystem,
        Filesystem\Driver\File $file,
        \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory,
        StoreManagerInterface $storeManager,
        WysiwygImageInterfaceFactory $wysiwygImage,
        MpHelper $mpHelper,
        \Magento\Framework\Json\Helper\Data $jsonHelper
    ) {
        $this->file = $file;
        parent::__construct($context, $filesystem, $file, $fileUploaderFactory, $storeManager, $wysiwygImage, $mpHelper, $jsonHelper);
    }

    /**
     * Upload image function
     *
     * @return void
     */
    public function execute()
    {
        $helper = $this->mpHelper;
        $isPartner = $helper->isSeller();
        $sellerId = $helper->getCustomerId();
        try {
            $target = $this->mediaDirectory->getAbsolutePath(
                'tmp/desc'
            );
            $fileUploader = $this->fileUploaderFactory->create(
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
            $resultData['url'] = $this->storeManager->getStore()->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
            ) . 'tmp/desc' . '/' . ltrim(str_replace('\\', '/', $resultData['file']), '/');
            $resultData['file'] = $resultData['file'] . '.tmp';
            if ($isPartner == 1) {
                $checkVal = $this->saveImageDesc($sellerId, $resultData['url'], $resultData['file']);
            }
            $this->getResponse()->representJson(
                $this->jsonHelper->jsonEncode($resultData)
            );
        } catch (\Exception $e) {
            $helper->logDataInLogger(
                "Controller_Wysiwyg_Gallery_Upload execute : ".$e->getMessage()
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
    }
}
