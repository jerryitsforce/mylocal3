<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Observer;

use Branch8\MarketplaceProduct\Api\Data\ProductVersionInterface;
use Branch8\MarketplaceProduct\Model\Product\BuildConfigurableProduct;
use Branch8\MarketplaceProduct\Model\Product\BuildCustomOptions;
use Branch8\MarketplaceProduct\Model\Product\BuildDownloadableProductLinks;
use Branch8\MarketplaceProduct\Model\ResourceModel\GetProductLogEntryByProductId;
use Magento\Catalog\Api\Data\ProductExtensionFactory;
use Magento\Catalog\Api\Data\ProductExtensionInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Media\Config as MediaConfig;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory as OptionsFactory;
use Magento\Downloadable\Model\LinkFactory as DownloadableLinkFactory;
use Magento\Downloadable\Model\SampleFactory as DownloadableSampleFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\CollectionFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

class ProcessProductPreviewObserver implements ObserverInterface
{
    /**
     * @var string
     */
    private const LOG_PREFIX = 'Branch8_MarketplaceProduct::ProcessProductPreviewObserver';

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var MediaConfig
     */
    private MediaConfig $mediaConfig;

    /**
     * @var Filesystem
     */
    private Filesystem $filesystem;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var OptionsFactory
     */
    private OptionsFactory $optionsFactory;

    /**
     * @var ProductExtensionFactory
     */
    private ProductExtensionFactory $extensionAttributesFactory;

    /**
     * @var DownloadableLinkFactory
     */
    private DownloadableLinkFactory $downloadableLinkFactory;

    /**
     * @var DownloadableSampleFactory
     */
    private DownloadableSampleFactory $downloadableSampleFactory;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $dataCollectionFactory;

    /**
     * @var GetProductLogEntryByProductId
     */
    private GetProductLogEntryByProductId $getProductLogEntryByProductId;

    /**
     * @var BuildCustomOptions
     */
    private BuildCustomOptions $buildCustomOptions;

    /**
     * ProcessProductPreviewObserver constructor.
     *
     * @param LoggerInterface $logger
     * @param RequestInterface $request
     * @param MediaConfig $mediaConfig
     * @param Filesystem $filesystem
     * @param SerializerInterface $serializer
     * @param OptionsFactory $optionsFactory
     * @param ProductExtensionFactory $extensionAttributesFactory
     * @param DownloadableLinkFactory $downloadableLinkFactory
     * @param DownloadableSampleFactory $downloadableSampleFactory
     * @param CollectionFactory $dataCollectionFactory
     * @param GetProductLogEntryByProductId $getProductLogEntryByProductId
     * @param BuildCustomOptions $buildCustomOptions
     */
    public function __construct(
        LoggerInterface               $logger,
        RequestInterface              $request,
        MediaConfig                   $mediaConfig,
        Filesystem                    $filesystem,
        SerializerInterface           $serializer,
        OptionsFactory                $optionsFactory,
        ProductExtensionFactory       $extensionAttributesFactory,
        DownloadableLinkFactory       $downloadableLinkFactory,
        DownloadableSampleFactory     $downloadableSampleFactory,
        CollectionFactory             $dataCollectionFactory,
        GetProductLogEntryByProductId $getProductLogEntryByProductId,
        BuildCustomOptions            $buildCustomOptions
    ){
        $this->logger = $logger;
        $this->request = $request;
        $this->mediaConfig = $mediaConfig;
        $this->filesystem = $filesystem;
        $this->serializer = $serializer;
        $this->optionsFactory = $optionsFactory;
        $this->extensionAttributesFactory = $extensionAttributesFactory;
        $this->downloadableLinkFactory = $downloadableLinkFactory;
        $this->downloadableSampleFactory = $downloadableSampleFactory;
        $this->dataCollectionFactory = $dataCollectionFactory;
        $this->getProductLogEntryByProductId = $getProductLogEntryByProductId;
        $this->buildCustomOptions = $buildCustomOptions;
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer): void
    {
        if ($this->request->getFullActionName() === 'marketplacectrl_catalog_preview') {
            /** @var Product $product */
            $product = $observer->getEvent()->getProduct();
            $product->setStatus(Status::STATUS_ENABLED);
            $logEntry = $this->getProductLogEntryByProductId->execute((int)$product->getId());
            if (!empty($logEntry[ProductVersionInterface::ADDITIONAL_INFORMATION])) {
                $additionalInfo = (string)$logEntry[ProductVersionInterface::ADDITIONAL_INFORMATION];
                $additionalInfo = $this->serializer->unserialize($additionalInfo);

                /** @var ProductExtensionInterface $extension */
                $extension = $product->getExtensionAttributes() ?? $this->extensionAttributesFactory->create();

                foreach ($additionalInfo as $key => $value) {
                    if ($key === ProductInterface::SKU) {
                        $key = "new_$key";
                    }
                    if ($key === 'image_gallery') {
                        // Handle preview for image gallery
                        $gallery = $this->processImageGallery($additionalInfo['image_gallery']['after']);
                        $product->setMediaGalleryImages($gallery);
                    } elseif (in_array($key, ['downloadable_link', 'downloadable_sample'])) {
                        // Handle preview for downloadable product only
                        $this->processDownloadableData($product, $value['after'], $key);
                    } elseif ($key === BuildConfigurableProduct::ATTRIBUTES_DATA) {
                        // Handle preview for configurable product only
                        $configurableOptions = [];
                        $attributesData = $value['after'] ?? [];
                        $attributeIds = $product->getTypeInstance()->getUsedProductAttributeIds($product);
                        if ($attributesData) {
                            $attributeIds = array_unique(array_merge($attributeIds, array_keys($attributesData)));
                            $product->getTypeInstance()->setUsedProductAttributes($product, $attributeIds);
                            $configurableOptions = $this->optionsFactory->create($attributesData);
                        }
                        $extension->setConfigurableProductOptions($configurableOptions);
                        $product->setData($key, $attributesData);
                    } elseif ($key === BuildConfigurableProduct::ASSOCIATED_PRODUCT_IDS) {
                        // Handle preview for configurable product only
                        $associatedProductIds = $value['after'] ?? [];
                        $extension->setConfigurableProductLinks($associatedProductIds);
                        $product->setData($key, $associatedProductIds);
                    } elseif ($key === BuildCustomOptions::KEY_CUSTOM_OPTIONS) {
                        // Add changes related to custom options product
                        $this->buildCustomOptions->execute($product, $value['after'], true);
                    } elseif ($key === 'shipping_method') {
                        if (!empty($value['after'])) {
                            $data = is_array($value['after']) ? implode(',', $value['after']) : $value['after'];
                            $product->setData($key, $data);
                        }
                    } else {
                        $product->setData($key, $value['after'] ?? null);
                    }
                }
                $product->setExtensionAttributes($extension);
            }
        }
    }

    /**
     * Process the image gallery.
     *
     * @param array $images
     *
     * @return Collection|null
     */
    private function processImageGallery(array $images): ?Collection
    {
        try {
            $baseMediaPath = $this->mediaConfig->getBaseMediaPath();
            $baseTmpMediaPath = $this->mediaConfig->getBaseTmpMediaPath();
            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);

            $collection = $this->dataCollectionFactory->create();
            foreach ($images as $image) {
                if (isset($image['removed']) && $image['removed'] == '1') {
                    continue;
                }
                $file = $image['file'] ?? null;
                $filePath = $mediaDirectory->getAbsolutePath($baseMediaPath . $file);
                $url = $this->mediaConfig->getBaseMediaUrl() . $file;
                if (!$mediaDirectory->isFile($filePath)) {
                    $filePath = $mediaDirectory->getAbsolutePath($baseTmpMediaPath . $file);
                    $url = $this->mediaConfig->getBaseTmpMediaUrl() . $file;
                    $file = $file.'.tmp';
                }

                $data = array_merge(
                    $image,
                    [
                        'media_type' => 'image',
                        'position_default' => $image['position'] ?? null,
                        'disabled_default' => $image['disabled'] ?? null,
                        'file' => $file,
                        'path' => $filePath,
                        'url' => $url
                    ]
                );
                $collection->addItem(new DataObject($data));

            }
            return $collection;
        } catch (\Exception $e) {
            $this->logger->error(self::LOG_PREFIX, ['exception' => $e]);
            return null;
        }
    }

    /**
     * Process the downloadable product data (links and samples).
     *
     * @param Product $product
     * @param array $data
     * @param string $type
     *
     * @return void
     */
    private function processDownloadableData(Product $product, array $data, string $type): void
    {
        $collection = [];
        if ($type === BuildDownloadableProductLinks::DOWNLOADABLE_LINK) {
            foreach ($data as $link) {
                $model = $this->downloadableLinkFactory->create(['data' => $link]);
                $model->setProductId($product->getId());
                $model->setSku($product->getSku());
                $model->setProduct($product);
                $model->setSampleType($link['sample']['type'] ?? null);
                $model->setSampleFile($link['sample']['file'] ?? null);
                $model->setSampleUrl($link['sample']['url'] ?? null);

                $collection[] = $model;
            }
            $product->setDownloadableLinks($collection);
        } elseif ($type === BuildDownloadableProductLinks::DOWNLOADABLE_SAMPLE) {
            foreach ($data as $sample) {
                $model = $this->downloadableSampleFactory->create(['data' => $sample]);
                $model->setProductId($product->getId());
                $model->setSku($product->getSku());
                $model->setProduct($product);
                $model->setSampleType($sample['type'] ?? null);
                $model->setSampleFile($sample['file'] ?? null);
                $model->setSampleUrl($sample['url'] ?? null);

                $collection[] = $model;
            }
            $product->setDownloadableSamples($collection);
        }
    }
}
