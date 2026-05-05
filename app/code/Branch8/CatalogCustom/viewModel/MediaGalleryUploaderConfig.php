<?php
declare(strict_types=1);

namespace Branch8\CatalogCustom\viewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;

class MediaGalleryUploaderConfig implements ArgumentInterface
{
    private \Branch8\MarketplaceStaging\Model\MediaGalleryUploaderConfig $config;

    /**
     * @param \Branch8\MarketplaceStaging\Model\MediaGalleryUploaderConfig $config
     */
    public function __construct(\Branch8\MarketplaceStaging\Model\MediaGalleryUploaderConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @return \Branch8\MarketplaceStaging\Model\MediaGalleryUploaderConfig
     */
    public function getUploaderConfig()
    {
        return $this->config;
    }
}
