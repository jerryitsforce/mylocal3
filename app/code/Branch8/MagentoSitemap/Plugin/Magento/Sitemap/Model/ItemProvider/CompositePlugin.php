<?php
declare(strict_types=1);

namespace Branch8\MagentoSitemap\Plugin\Magento\Sitemap\Model\ItemProvider;

use Branch8\MagentoSitemap\Model\ConfigData;

class CompositePlugin
{
    /**
     * Item resolvers
     *
     * @var ItemProviderInterface[]
     */
    private $itemProviders;
    /**
     * @var array
     */
    private $exclusiveUrls = [];
    /**
     * @var ConfigData
     */
    private ConfigData $configData;
    /**
     * @var array
     */
    private array $disableItems;

    /**
     * @param ConfigData $configData
     * @param array $disableItems
     * @param array $itemProviders
     */
    public function __construct(
        ConfigData $configData,
        array      $disableItems = [],
        array      $itemProviders = []
    )
    {
        $this->configData = $configData;
        $this->itemProviders = $itemProviders;
        $this->initExecludeUrls();
        $this->disableItems = $disableItems;
    }

    /**
     * @return void
     */
    private function initExecludeUrls()
    {
        $this->exclusiveUrls = [];
        $config = trim((string)$this->configData->getConfig('sitemap/generate/exclude_urls'));
        if ($config) {
            $this->exclusiveUrls = preg_split('/\r\n|\r|\n/', trim($config));
            $this->exclusiveUrls = array_filter(array_map('trim', $this->exclusiveUrls));
        }
    }

    /**
     * @param $url
     * @return bool
     */
    private function isExclusiveUrl($url)
    {
        foreach ($this->exclusiveUrls as $exclude) {
            $exclude = trim($exclude);
            if (stripos($url, $exclude) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function aroundGetItems($subject, callable $process, $storeId)
    {
        $items = [];

        foreach ($this->itemProviders as $key => $resolver) {
            if (in_array($key, $this->disableItems)) {
                continue;
            }
            foreach ($resolver->getItems($storeId) as $item) {
                if ($this->isExclusiveUrl($item->getUrl())) {
                    continue;
                }
                $items[] = $item;
            }
        }
        return $items;
    }
}
