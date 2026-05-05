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
use Psr\Log\LoggerInterface;


/**
 * Attachment Uploadder
 */
class ImageProfileUploader extends \Magento\Catalog\Model\ImageUploader
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
     * @param Database $coreFileStorageDatabase
     * @param Filesystem $filesystem
     * @param UploaderFactory $uploaderFactory
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
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
        $this->fileNameLookup = $fileNameLookup ?? ObjectManager::getInstance()->get(Name::class);

    }
}
