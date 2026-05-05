<?php
namespace Branch8\Catalog\Plugin\Magento\MediaGallery\Model\Asset\Command;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\IntegrationException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\MediaGalleryApi\Api\Data\AssetInterface;
use Magento\MediaGalleryApi\Api\Data\AssetInterfaceFactory;
use Psr\Log\LoggerInterface;

class GetByPath
{
    private const TABLE_MEDIA_GALLERY_ASSET = 'media_gallery_asset';

    private const MEDIA_GALLERY_ASSET_PATH = 'path';

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var AssetInterfaceFactory
     */
    private $mediaAssetFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * GetByPath constructor.
     *
     * @param ResourceConnection $resourceConnection
     * @param AssetInterfaceFactory $mediaAssetFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        AssetInterfaceFactory $mediaAssetFactory,
        LoggerInterface $logger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->mediaAssetFactory = $mediaAssetFactory;
        $this->logger = $logger;
    }

    /**
     * Return media asset
     *
     * @param $subject
     * @param callable $proceed
     * @param string $path
     *
     * @return AssetInterface
     * @throws IntegrationException
     */
    public function aroundExecute($subject, callable $proceed, string $path): AssetInterface
    {
        try {
            $connection = $this->resourceConnection->getConnection();
            $select = $connection->select()
                ->from($this->resourceConnection->getTableName(self::TABLE_MEDIA_GALLERY_ASSET))
                ->where(self::MEDIA_GALLERY_ASSET_PATH . ' = ?', $path);
            $data = $connection->query($select)->fetch();

            if (empty($data)) {
                $message = __('There is no such media asset with path "%1"', $path);
                throw new NoSuchEntityException($message);
            }

            return $this->mediaAssetFactory->create(
                [
                    'id' => $data['id'],
                    'path' => $data['path'],
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'source' => $data['source'],
                    'hash' => $data['hash'],
                    'contentType' => $data['content_type'],
                    'width' => $data['width'],
                    'height' => $data['height'],
                    'size' => $data['size'],
                    'createdAt' => $data['created_at'],
                    'updatedAt' => $data['updated_at'],
                ]
            );
        } catch (\Exception $exception) {
            //$this->logger->critical($exception);
            $message = __('An error occurred during get media asset list: %1', $exception->getMessage());
            throw new IntegrationException($message, $exception);
        }
    }
}
