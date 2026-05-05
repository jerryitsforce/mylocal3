<?php

namespace Branch8\Homepage\Block\Head;

use Magento\Framework\View\Element\Template;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Cms\Model\ResourceModel\Block\CollectionFactory;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\UrlInterface;

class Meta extends Template
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var CollectionFactory
     */
    protected $blockCollectionFactory;

    /**
     * @var FilterProvider
     */
    protected $filterProvider;

    /**
     * @param Template\Context $context
     * @param StoreManagerInterface $storeManager
     * @param CollectionFactory $blockCollectionFactory
     * @param FilterProvider $filterProvider
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        StoreManagerInterface $storeManager,
        CollectionFactory $blockCollectionFactory,
        FilterProvider $filterProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->storeManager = $storeManager;
        $this->blockCollectionFactory = $blockCollectionFactory;
        $this->filterProvider = $filterProvider;
    }

    /**
     * Get homepage content HTML and extract data-background-images into array
     *
     * @return array Array of elements with data-background-images attribute
     */
    public function getBackgroundImagesForViewSource(): array
    {
        try {
            $storeId = $this->storeManager->getStore()->getId();
            
            // Get CMS block with identifier = 'banner_slider_feature_section'
            // order by update_time desc limit 1
            $collection = $this->blockCollectionFactory->create();
            $collection->addFieldToFilter('identifier', 'banner_slider_feature_section')
                ->addFieldToFilter('is_active', 1)
                ->addStoreFilter($storeId)
                ->setOrder('update_time', 'DESC')
                ->setPageSize(1)
                ->setCurPage(1);
            
            $block = $collection->getFirstItem();
            
            if (!$block->getId()) {
                return [];
            }
            
            $block->setStoreId($storeId);
            $blockContent = $block->getContent();
            
            if (!$blockContent) {
                return [];
            }
            
            // Process content through filter (handles widgets, variables, etc.)
            $filteredHtml = $this->filterProvider->getBlockFilter()
                ->setStoreId($storeId)
                ->filter($blockContent);

            // Prefer filtered HTML; fallback to raw content if nothing extracted
            $result = $this->extractBackgroundImages($filteredHtml);
            if (empty($result)) {
                $result = $this->extractBackgroundImages($blockContent);
            }

            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Extract background image URLs from HTML content.
     *
     * @param string $html
     * @return array
     */
    private function extractBackgroundImages(string $html): array
    {
        if (empty($html)) {
            return [];
        }

        $result = [];
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//*[@data-background-images]');
        foreach ($nodes as $node) {
            $dataBackgroundImages = $node->getAttribute('data-background-images');
            if (!$dataBackgroundImages) {
                continue;
            }

            // Remove backslashes before HTML entities (e.g., \&quot; to &quot;)
            $cleaned = str_replace('\\&quot;', '&quot;', $dataBackgroundImages);
            $cleaned = str_replace('\\"', '"', $cleaned);

            // Decode HTML entities first (e.g., &quot; to ")
            $decodedHtml = html_entity_decode($cleaned, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            // Decode JSON
            $decoded = json_decode($decodedHtml, true);
            if (!$decoded || !is_array($decoded) || empty($decoded)) {
                continue;
            }

            if (isset($decoded['desktop_image'])) {
                $desktopUrl = $this->convertMediaUrl($decoded['desktop_image']);
                if ($desktopUrl && !in_array($desktopUrl, $result, true)) {
                    $result[] = $desktopUrl;
                }
            }

            if (isset($decoded['mobile_image'])) {
                $mobileUrl = $this->convertMediaUrl($decoded['mobile_image']);
                if ($mobileUrl && !in_array($mobileUrl, $result, true)) {
                    $result[] = $mobileUrl;
                }
            }
        }

        return $result;
    }

    /**
     * Convert {{media url=...}} format to full URL
     *
     * @param string $mediaUrl
     * @return string
     */
    protected function convertMediaUrl($mediaUrl)
    {
        if (empty($mediaUrl)) {
            return '';
        }

        // Check if it's in {{media url=...}} format
        if (preg_match('/\{\{media\s+url=([^}]+)\}\}/', $mediaUrl, $matches)) {
            $mediaPath = trim($matches[1]);
            // Get base media URL from store
            $baseMediaUrl = $this->storeManager->getStore()
                ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
            return rtrim($baseMediaUrl, '/') . '/' . ltrim($mediaPath, '/');
        }

        // If already a full URL, return as is
        if (filter_var($mediaUrl, FILTER_VALIDATE_URL)) {
            return $mediaUrl;
        }

        // Otherwise, treat as relative path and prepend media URL
        $baseMediaUrl = $this->storeManager->getStore()
            ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        return rtrim($baseMediaUrl, '/') . '/' . ltrim($mediaUrl, '/');
    }

}

