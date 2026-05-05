<?php

namespace Branch8\MarketplaceProduct\Model;

use Branch8\MarketplaceProduct\Helper\Images;
use Branch8\MarketplaceStaging\Model\MediaGalleryUploaderConfig;
use Magento\Framework\Validation\ValidationException;

class FileUploader extends \Magento\MediaStorage\Model\File\Uploader
{
    private MediaGalleryUploaderConfig $mediaGalleryConfig;

    /**
     * @param string $fileId
     * @param \Magento\MediaStorage\Helper\File\Storage\Database $coreFileStorageDb
     * @param \Magento\MediaStorage\Helper\File\Storage $coreFileStorage
     * @param \Magento\MediaStorage\Model\File\Validator\NotProtectedExtension $validator
     * @param MediaGalleryUploaderConfig $mediaGalleryConfig
     * @param \Magento\Framework\Filesystem|null $filesystem
     */
    public function __construct(
        $fileId,
        \Magento\MediaStorage\Helper\File\Storage\Database $coreFileStorageDb,
        \Magento\MediaStorage\Helper\File\Storage $coreFileStorage,
        \Magento\MediaStorage\Model\File\Validator\NotProtectedExtension $validator,
        MediaGalleryUploaderConfig $mediaGalleryConfig,
        \Magento\Framework\Filesystem $filesystem = null
    )
    {
        $this->mediaGalleryConfig = $mediaGalleryConfig;
        parent::__construct($fileId, $coreFileStorageDb, $coreFileStorage, $validator, $filesystem);
    }

    /**
     * @return void
     * @throws ValidationException
     */
    protected function _validateFile()
    {
        $size = $this->_file['size'];
        if ($size > $this->mediaGalleryConfig->getMaxUploadSize()) {
            throw new ValidationException($this->mediaGalleryConfig->getMaxSizeError());
        }
        return parent::_validateFile();
    }

    /**
     * @param $destinationFolder
     * @param $newFileName
     * @return array|bool
     * @throws \Exception
     */
    public function save($destinationFolder, $newFileName = null)
    {
        if (!$newFileName) {
            $newFileName = Images::normalizeImageFileName($this->_file['name']);
        }
        return parent::save($destinationFolder, $newFileName);
    }
}
