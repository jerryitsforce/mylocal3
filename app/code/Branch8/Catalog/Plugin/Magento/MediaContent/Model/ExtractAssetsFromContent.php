<?php
namespace Branch8\Catalog\Plugin\Magento\MediaContent\Model;

use Magento\MediaContentApi\Model\SearchPatternConfigInterface;
use Magento\MediaGalleryApi\Api\Data\AssetInterface;
use Magento\MediaGalleryApi\Model\Asset\Command\GetByPathInterface;
use Psr\Log\LoggerInterface;

class ExtractAssetsFromContent
{
    /**
     * @var SearchPatternConfigInterface
     */
    private $searchPatternConfig;

    /**
     * @var GetByPathInterface
     */
    private $getMediaAssetByPath;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param SearchPatternConfigInterface $searchPatternConfig
     * @param GetByPathInterface $getMediaAssetByPath
     * @param LoggerInterface $logger
     */
    public function __construct(
        SearchPatternConfigInterface $searchPatternConfig,
        GetByPathInterface $getMediaAssetByPath,
        LoggerInterface $logger
    ) {
        $this->searchPatternConfig = $searchPatternConfig;
        $this->getMediaAssetByPath = $getMediaAssetByPath;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function aroundExecute($subject, callable $proceed, string $content): array
    {
        $matchesArrays = [];

        foreach ($this->searchPatternConfig->get() as $pattern) {
            if (empty($pattern)) {
                continue;
            }

            preg_match_all($pattern, $content, $matches, PREG_PATTERN_ORDER);

            if (!empty($matches[1])) {
                $matchesArrays[] = $matches[1];
            }
        }

        return $this->getAssetsByPaths(array_unique(array_merge([], ...$matchesArrays)));
    }

    /**
     * Get media assets by paths array
     *
     * @param array $paths
     * @return AssetInterface[]
     */
    private function getAssetsByPaths(array $paths): array
    {
        $assets = [];

        foreach ($paths as $path) {
            $path = htmlspecialchars_decode($path);
            $path = trim($path, '"\'');
            try {
                /** @var AssetInterface $asset */
                $asset = $this->getMediaAssetByPath->execute($this->getPathStartingWithSlash($path));
                $assets[$asset->getId()] = $asset;
            } catch (\Exception $exception) {
                //$this->logger->critical($exception);
            }
        }

        return $assets;
    }

    /**
     * Ensure the extracted paths matches the standard format
     *
     * @param string $path
     * @return string
     */
    private function getPathStartingWithSlash(string $path): string
    {
        $normalizedPath = ltrim($path, '/');
        if (strpos($normalizedPath, '/') !== false) {
            return $normalizedPath;
        }
        return '/' . $normalizedPath;
    }
}
