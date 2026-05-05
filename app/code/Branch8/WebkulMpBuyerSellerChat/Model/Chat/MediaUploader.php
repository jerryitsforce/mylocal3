<?php
declare(strict_types=1);

namespace Branch8\WebkulMpBuyerSellerChat\Model\Chat;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Name;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Branch8\WebkulMpBuyerSellerChat\Helper\Logger as CustomLogger;
use Psr\Log\LoggerInterface;


/**
 * Attachment Uploadder
 */
class MediaUploader extends \Magento\Catalog\Model\ImageUploader
{
    /**
     * @var UploaderFactory
     */
    private $uploaderFactory;
    /**
     * @var array
     */
    protected $allowedExtensions = [];
    /**
     *
     */
    private $allowedMimeTypes;
    /**
     * @var Name|mixed
     */

    private $fileNameLookup;
    /**
     * @var CustomLogger
     */
    private $customLogger;

    /**
     * @param Database $coreFileStorageDatabase
     * @param Filesystem $filesystem
     * @param UploaderFactory $uploaderFactory
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param CustomLogger $customLogger
     * @param $baseTmpPath
     * @param $basePath
     * @param $allowedExtensions
     * @param $allowedMimeTypes
     * @param Name|null $fileNameLookup
     */
    public function __construct(
        Database              $coreFileStorageDatabase,
        Filesystem            $filesystem,
        UploaderFactory       $uploaderFactory,
        StoreManagerInterface $storeManager,
        LoggerInterface       $logger,
        CustomLogger          $customLogger,
        string                $baseTmpPath = 'mpchatsystem/tmp/attachment',
        string                $basePath = 'mpchatsystem/conversation',
        array                 $allowedExtensions = [],
        array                 $allowedMimeTypes = [],
        Name                  $fileNameLookup = null
    )
    {
        $this->allowedExtensions = MediaConfig::getUploadAttachmentAllowedExtensions();
        $this->allowedMimeTypes = MediaConfig::getUploadAttachmentAllowedMimeType();
        parent::__construct(
            $coreFileStorageDatabase,
            $filesystem,
            $uploaderFactory,
            $storeManager,
            $logger,
            $baseTmpPath,
            $basePath,
            $this->allowedExtensions,
            $this->allowedMimeTypes,
            $fileNameLookup
        );
        $this->uploaderFactory = $uploaderFactory;
        $this->customLogger = $customLogger;
        $this->fileNameLookup = $fileNameLookup ?? ObjectManager::getInstance()->get(Name::class);

    }

    /**
     * @param $fileName
     * @return string
     */
    private function cleanFileName($fileName) {
        $lastDotPosition = strrpos($fileName, '.');
        if ($lastDotPosition !== false) {
            $name = substr($fileName, 0, $lastDotPosition); // Get the name part
            $extension = substr($fileName, $lastDotPosition); // Get the extension part
        } else {
            $name = $fileName;
            $extension = '';
        }
        $name = preg_replace('/[^A-Za-z0-9_\-]/', '', $name);
        $name = str_replace(' ', '_', $name);
        $name = trim($name, '_');
        return $name . $extension; // Return cleaned name with extension
    }

    /**
     * @param string $uniqueId
     * @param string $fileId
     * @return array|bool
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function saveFileToDestinationFolder(string $uniqueId, string $fileId)
    {
        $conversationPath = $this->getBasePath() . '/' . $uniqueId;
        /** @var \Magento\MediaStorage\Model\File\Uploader $uploader */
        $uploader = $this->uploaderFactory->create(['fileId' => $fileId]);
        $uploader->setAllowedExtensions($this->getAllowedExtensions());
        $uploader->setAllowRenameFiles(true);
        $uploader->setFilenamesCaseSensitivity(true);
        if (!$uploader->checkMimeType($this->allowedMimeTypes)) {
            throw new LocalizedException(__('File validation failed.'));
        }
        $maxFileSize = 5 * 1024 * 1024; // 5MB in bytes
        if ($uploader->getFileSize() > $maxFileSize) {
            throw new LocalizedException(__('The file is too large. Maximum allowed size is %1 MB.', $maxFileSize / (1024 * 1024)));
        }
        $result = $uploader->save(
            $this->mediaDirectory->getAbsolutePath($conversationPath),
            $this->cleanFileName($_POST['name'])
        );

        if (!$result) {
            throw new LocalizedException(__('File can not be saved to the destination folder.'));
        }
        unset($result['path']);

        /**
         * Workaround for prototype 1.7 methods "isJSON", "evalJSON" on Windows OS
         */
        $result['tmp_name'] = isset($result['tmp_name']) ? str_replace('\\', '/', $result['tmp_name']) : '';
        $result['url'] = $this->storeManager
                ->getStore()
                ->getBaseUrl(
                    \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
                ) . $this->getFilePath($conversationPath, $result['file']);
        $result['name'] = $result['file'];
        if (isset($result['file'])) {
            try {
                $relativePath = rtrim($conversationPath, '/') . '/' . ltrim($result['file'], '/');
                $this->coreFileStorageDatabase->saveFile($relativePath);
            } catch (\Exception $e) {
                $this->customLogger->critical($e);
                throw new LocalizedException(
                    __('Something went wrong while saving the file(s).'),
                    $e
                );
            }
        }
        return $result;
    }
}
