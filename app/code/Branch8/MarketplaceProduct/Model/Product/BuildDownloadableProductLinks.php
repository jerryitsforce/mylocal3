<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model\Product;

use Magento\Catalog\Api\Data\ProductExtensionFactory;
use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Downloadable\Api\Data\LinkInterfaceFactory as LinkFactory;
use Magento\Downloadable\Api\Data\SampleInterfaceFactory as SampleFactory;
use Magento\Downloadable\Model\Link\Builder as LinkBuilder;
use Magento\Downloadable\Model\Sample\Builder as SampleBuilder;
use Magento\Framework\Exception\LocalizedException;

class BuildDownloadableProductLinks
{
    /**#@+
     * Constants for keys of product link.
     */
    public const DOWNLOADABLE_LINK = 'downloadable_link';
    public const DOWNLOADABLE_SAMPLE = 'downloadable_sample';
    /**#@-*/

    /**
     * @var LinkBuilder
     */
    private LinkBuilder $linkBuilder;

    /**
     * @var LinkFactory
     */
    private LinkFactory $linkFactory;

    /**
     * @var SampleBuilder
     */
    private SampleBuilder $sampleBuilder;

    /**
     * @var SampleFactory
     */
    private SampleFactory $sampleFactory;

    /**
     * @var ProductExtensionFactory
     */
    private ProductExtensionFactory $extensionAttributesFactory;

    /**
     * BuildDownloadableProductLinks constructor.
     *
     * @param LinkBuilder $linkBuilder
     * @param LinkFactory $linkFactory
     * @param SampleBuilder $sampleBuilder
     * @param SampleFactory $sampleFactory
     * @param ProductExtensionFactory $extensionAttributesFactory
     */
    public function __construct(
        LinkBuilder             $linkBuilder,
        LinkFactory             $linkFactory,
        SampleBuilder           $sampleBuilder,
        SampleFactory           $sampleFactory,
        ProductExtensionFactory $extensionAttributesFactory
    ) {
        $this->linkBuilder = $linkBuilder;
        $this->linkFactory = $linkFactory;
        $this->sampleBuilder = $sampleBuilder;
        $this->sampleFactory = $sampleFactory;
        $this->extensionAttributesFactory = $extensionAttributesFactory;
    }

    /**
     * Build downloadable product links.
     *
     * @param ProductInterface $product
     * @param array $data
     * @param string $type
     *
     * @return void
     *
     * @throws LocalizedException
     */
    public function execute(ProductInterface $product, array $data, string $type): void
    {
        /** @var ProductExtensionInterface $extension */
        $extension = $product->getExtensionAttributes() ?? $this->extensionAttributesFactory->create();

        if ($type === self::DOWNLOADABLE_LINK) {
            $links = [];
            foreach ($data as $link) {
                if (!$link || (isset($link['is_delete']) && $link['is_delete'])) {
                    continue;
                }
                $links[] = $this->linkBuilder->setData($link)
                    ->build($this->linkFactory->create());
            }
            $extension->setDownloadableProductLinks($links);
        }

        if ($type === self::DOWNLOADABLE_SAMPLE) {
            $samples = [];
            foreach ($data as $sample) {
                if (!$sample || (isset($sample['is_delete']) && $sample['is_delete'])) {
                    continue;
                }
                $samples[] = $this->sampleBuilder->setData($sample)
                    ->build($this->sampleFactory->create());
            }
            $extension->setDownloadableProductSamples($samples);
        }

        $product->setExtensionAttributes($extension);
        if ($product->getLinksPurchasedSeparately()) {
            $product->setTypeHasRequiredOptions(true)->setRequiredOptions(true);
        } else {
            $product->setTypeHasRequiredOptions(false)->setRequiredOptions(false);
        }
    }
}
