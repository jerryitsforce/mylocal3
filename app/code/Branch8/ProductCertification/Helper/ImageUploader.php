<?php
declare(strict_types=1);

namespace Branch8\ProductCertification\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Image\AdapterFactory;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Image upload helper — resizes icon to 64×64 px after upload
 */
class ImageUploader extends AbstractHelper
{
    public const UPLOAD_DIR    = 'branch8/certification/icon';
    public const ICON_WIDTH    = 64;
    public const ICON_HEIGHT   = 64;

    /**
     * @var UploaderFactory
     */
    private UploaderFactory $uploaderFactory;

    /**
     * @var Filesystem
     */
    private Filesystem $filesystem;

    /**
     * @var AdapterFactory
     */
    private AdapterFactory $imageAdapterFactory;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @param Context $context
     * @param UploaderFactory $uploaderFactory
     * @param Filesystem $filesystem
     * @param AdapterFactory $imageAdapterFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        AdapterFactory $imageAdapterFactory,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->uploaderFactory     = $uploaderFactory;
        $this->filesystem          = $filesystem;
        $this->imageAdapterFactory = $imageAdapterFactory;
        $this->storeManager        = $storeManager;
    }

    /**
     * Move file from tmp directory to permanent directory and resize it.
     *
     * @param string $fileName
     * @return string  relative path stored in DB
     * @throws LocalizedException
     */
    public function moveFileFromTmp(string $fileName): string
    {
        $mediaDir = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $tmpDir   = 'tmp/' . self::UPLOAD_DIR;
        $destDir  = self::UPLOAD_DIR;

        $srcPath  = $mediaDir->getAbsolutePath($tmpDir . '/' . $fileName);
        $destPath = $mediaDir->getAbsolutePath($destDir . '/' . $fileName);

        if (!$mediaDir->isExist($destDir)) {
            $mediaDir->create($destDir);
        }

        $mediaDir->getDriver()->rename($srcPath, $destPath);

        // Resize to fixed dimensions
        $this->resizeImage($destPath);

        return self::UPLOAD_DIR . '/' . $fileName;
    }

    /**
     * Upload icon file from the HTTP request.
     *
     * @param string $fileId  Form input name
     * @return array ['name', 'path', 'url']
     * @throws LocalizedException
     */
    public function uploadToTmp(string $fileId): array
    {
        $mediaDir = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $tmpPath  = $mediaDir->getAbsolutePath('tmp/' . self::UPLOAD_DIR);

        $uploader = $this->uploaderFactory->create(['fileId' => $fileId]);
        $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp']);
        $uploader->setAllowRenameFiles(true);
        $uploader->setFilesDispersion(false);

        $result = $uploader->save($tmpPath);

        if (empty($result['file'])) {
            throw new LocalizedException(__('File upload failed.'));
        }

        return [
            'name' => $result['file'],
            'file' => $result['file'],
            'size' => $result['size'],
            'type' => $result['type'],
            'tmp_name' => $result['path'] . '/' . $result['file'],
            'url'  => $this->getTmpUrl($result['file']),
        ];
    }

    /**
     * Get URL in tmp directory
     * @param string $filename
     * @return string
     */
    public function getTmpUrl(string $filename): string
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA)
            . 'tmp/' . self::UPLOAD_DIR . '/' . $filename;
    }

    /**
     * Get URL for a stored icon
     * @param string $path
     * @return string
     */
    public function getIconUrl(string $path): string
    {
        return $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA)
            . ltrim($path, '/');
    }

    /**
     * Resize image to fixed dimensions
     * @param string $absolutePath
     * @return void
     */
    private function resizeImage(string $absolutePath): void
    {
        $adapter = $this->imageAdapterFactory->create();
        $adapter->open($absolutePath);
        $adapter->constrainOnly(true);
        $adapter->keepAspectRatio(true);
        $adapter->keepFrame(true);
        $adapter->backgroundColor([255, 255, 255]);
        $adapter->resize(self::ICON_WIDTH, self::ICON_HEIGHT);
        $adapter->save($absolutePath);
    }
}
