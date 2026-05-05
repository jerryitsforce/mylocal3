<?php

declare(strict_types=1);

namespace Branch8\HelpDesk\Model\Ticket;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\Name;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Attachment Uploader
 */
class AttachmentUploader extends \Magento\Catalog\Model\ImageUploader
{
    /**
     * @var string
     */
    private const LOG_PREFIX = 'Branch8\HelpDesk\Model\Ticket\AttachmentUploader';

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

    private AttachmentUploaderConfig $attachmentUploaderConfig;

    /**
     * @param Database $coreFileStorageDatabase
     * @param Filesystem $filesystem
     * @param UploaderFactory $uploaderFactory
     * @param StoreManagerInterface $storeManager
     * @param AttachmentUploaderConfig $attachmentUploaderConfig
     * @param LoggerInterface $logger
     * @param string $baseTmpPath
     * @param string $basePath
     * @param array $allowedExtensions
     * @param array $allowedMimeTypes
     * @param Name|null $fileNameLookup
     */
    public function __construct(
        Database                        $coreFileStorageDatabase,
        Filesystem                      $filesystem,
        UploaderFactory                 $uploaderFactory,
        StoreManagerInterface           $storeManager,
        AttachmentUploaderConfig        $attachmentUploaderConfig,
        \Branch8\HelpDesk\Helper\Logger $logger,
        string                          $baseTmpPath = 'helpdesk/tmp/attachment',
        string                          $basePath = 'helpdesk/attachment',
        array                           $allowedExtensions = [],
        array                           $allowedMimeTypes = [],
        Name                            $fileNameLookup = null
    ) {
        $this->attachmentUploaderConfig = $attachmentUploaderConfig;
        $this->allowedExtensions = $this->attachmentUploaderConfig->getAllowExtensionFiles();
        $this->allowedMimeTypes = AttachmentUploaderConfig::getAllowUploadAttachmentAllowedMimeType($this->allowedExtensions);
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

        $this->fileNameLookup = $fileNameLookup ?? ObjectManager::getInstance()->get(Name::class);
    }

    /**
     * MoveFileFromTmp
     * @param $imageName
     * @param $returnRelativePath
     * @return string
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function moveFileFromTmp($imageName, $returnRelativePath = false)
    {
        $baseTmpPath = $this->getBaseTmpPath();
        $basePath = $this->getBasePath();
        $baseImagePath = $this->getFilePath(
            $basePath,
            $this->fileNameLookup->getNewFileName(
                $this->mediaDirectory->getAbsolutePath(
                    $this->getFilePath($basePath, $imageName)
                )
            )
        );
        $baseTmpImagePath = $this->getFilePath($baseTmpPath, $imageName);

        try {
            $this->coreFileStorageDatabase->renameFile(
                $baseTmpImagePath,
                $baseImagePath
            );
            $this->mediaDirectory->renameFile(
                $baseTmpImagePath,
                $baseImagePath
            );
        } catch (\Exception $e) {
            $this->logger->critical(self::LOG_PREFIX, ['exception' => $e]);
            throw new LocalizedException(__('Something went wrong while saving the file(s).'), $e);
        }

        return $returnRelativePath ? $baseImagePath : $imageName;
    }

    /**
     * Get absolute path.
     *
     * @param string|null $path
     *
     * @return string
     */
    public function getMediaAbsolutePath(string $path = null): string
    {
        return $this->mediaDirectory->getAbsolutePath($path);
    }
}
