<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Block\Product\Renderer;

use Branch8\MarketplaceProduct\Model\Product\BuildConfigurableProduct;
use Branch8\MarketplaceProduct\Model\Product\Preview\ConfigurableAttributeData as ConfigurableAttributeDataPreview;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Helper\Product as CatalogProduct;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Image\UrlBuilder;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\ConfigurableProduct\Helper\Data;
use Magento\ConfigurableProduct\Model\ConfigurableAttributeData;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable\Variations\Prices as VariationPrices;
use Magento\Customer\Helper\Session\CurrentCustomer;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Locale\Format;
use Magento\Framework\Locale\Format as LocaleFormat;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\ArrayUtils;
use Magento\Store\Model\Store;
use Magento\Swatches\Helper\Data as SwatchData;
use Magento\Swatches\Helper\Media as SwatchMediaHelper;
use Magento\Swatches\Model\SwatchAttributesProvider;

/**
 * Swatch renderer block.
 *
 * @method $this setCacheKey(string $cacheKey)
 */
class Configurable extends \Magento\Swatches\Block\Product\Renderer\Configurable
{
    /**
     * @inheritdoc
     */
    const SWATCH_RENDERER_TEMPLATE = 'Branch8_MarketplaceProduct::product/view/renderer/configurable.phtml';

    /**
     * @var LocaleFormat
     */
    private LocaleFormat $localeFormat;

    /**
     * @var VariationPrices
     */
    private VariationPrices $variationPrices;

    /**
     * @var FilterBuilder
     */
    private FilterBuilder $filterBuilder;

    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    /**
     * @var ProductAttributeRepositoryInterface
     */
    private ProductAttributeRepositoryInterface $productAttributeRepository;

    /**
     * @var ProductCollectionFactory
     */
    private ProductCollectionFactory $productCollectionFactory;

    /**
     * @var ConfigurableAttributeDataPreview
     */
    private ConfigurableAttributeDataPreview $configurableAttributeDataPreview;

    /**
     * Configurable constructor.
     *
     * @param Context $context
     * @param ArrayUtils $arrayUtils
     * @param EncoderInterface $jsonEncoder
     * @param Data $helper
     * @param CatalogProduct $catalogProduct
     * @param CurrentCustomer $currentCustomer
     * @param PriceCurrencyInterface $priceCurrency
     * @param ConfigurableAttributeData $configurableAttributeData
     * @param SwatchData $swatchHelper
     * @param SwatchMediaHelper $swatchMediaHelper
     * @param FilterBuilder $filterBuilder
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     * @param ProductCollectionFactory $productCollectionFactory
     * @param ConfigurableAttributeDataPreview $configurableAttributeDataPreview
     * @param array $data
     * @param SwatchAttributesProvider|null $swatchAttributesProvider
     * @param UrlBuilder|null $imageUrlBuilder
     * @param LocaleFormat|null $localeFormat
     * @param VariationPrices|null $variationPrices
     */
    public function __construct(
        Context                             $context,
        ArrayUtils                          $arrayUtils,
        EncoderInterface                    $jsonEncoder,
        Data                                $helper,
        CatalogProduct                      $catalogProduct,
        CurrentCustomer                     $currentCustomer,
        PriceCurrencyInterface              $priceCurrency,
        ConfigurableAttributeData           $configurableAttributeData,
        SwatchData                          $swatchHelper,
        SwatchMediaHelper                   $swatchMediaHelper,
        FilterBuilder                       $filterBuilder,
        SearchCriteriaBuilder               $searchCriteriaBuilder,
        ProductAttributeRepositoryInterface $productAttributeRepository,
        ProductCollectionFactory            $productCollectionFactory,
        ConfigurableAttributeDataPreview    $configurableAttributeDataPreview,
        array                               $data = [],
        SwatchAttributesProvider            $swatchAttributesProvider = null,
        UrlBuilder                          $imageUrlBuilder = null,
        Format                              $localeFormat = null,
        VariationPrices                     $variationPrices = null
    ) {
        parent::__construct(
            $context,
            $arrayUtils,
            $jsonEncoder,
            $helper,
            $catalogProduct,
            $currentCustomer,
            $priceCurrency,
            $configurableAttributeData,
            $swatchHelper,
            $swatchMediaHelper,
            $data,
            $swatchAttributesProvider,
            $imageUrlBuilder
        );
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->configurableAttributeDataPreview = $configurableAttributeDataPreview;
        $this->localeFormat = $localeFormat ?: ObjectManager::getInstance()->get(LocaleFormat::class);
        $this->variationPrices = $variationPrices ?: ObjectManager::getInstance()->get(VariationPrices::class);
    }

    /**
     * @inheritdoc
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getJsonConfig(): string
    {
        if (!$this->useDefaultRenderer()) {
            return parent::getJsonConfig();
        }

        $store = $this->getCurrentStore();
        $currentProduct = $this->getProduct();
        $attributesData = $this->configurableAttributeDataPreview->getAttributesData($currentProduct, (array)$this->getAllowProducts());

        $config = [
            'attributes' => $attributesData['attributes'],
            'template' => str_replace('%s', '<%- data.price %>', $store->getCurrentCurrency()->getOutputFormat()),
            'currencyFormat' => $store->getCurrentCurrency()->getOutputFormat(),
            'optionPrices' => $this->getOptionPrices(),
            'priceFormat' => $this->localeFormat->getPriceFormat(),
            'prices' => $this->variationPrices->getFormattedPrices($this->getProduct()->getPriceInfo()),
            'productId' => $currentProduct->getId(),
            'chooseText' => __('Choose an Option...'),
            'images' => $this->getOptionImages(),
        ];

        if ($currentProduct->hasPreconfiguredValues() && !empty($attributesData['defaultValues'])) {
            $config['defaultValues'] = $attributesData['defaultValues'];
        }

        $config = array_merge($config, $this->_getAdditionalConfig());

        return $this->jsonEncoder->encode($config);
    }

    /**
     * Use default renderer.
     *
     * @return bool
     */
    private function useDefaultRenderer(): bool
    {
        $productIds = $this->getProduct()->getData(BuildConfigurableProduct::ASSOCIATED_PRODUCT_IDS);
        return (bool)$productIds;
    }

    /**
     * @inheritdoc
     */
    public function getAllowProducts()
    {
        if (!$this->useDefaultRenderer()) {
            return parent::getAllowProducts();
        }

        $product = $this->getProduct();
        $productIds = $product->getData(BuildConfigurableProduct::ASSOCIATED_PRODUCT_IDS);

        $attributeIds = $product->getTypeInstance()->getUsedProductAttributeIds($product) ?: [];
        $attributesForSelect = $this->getConfigurableAttributes($attributeIds);

        $productCollection = $this->productCollectionFactory->create()
            ->addAttributeToSelect($attributesForSelect)
            ->addAttributeToFilter(ProductInterface::STATUS, Status::STATUS_ENABLED)
            ->addIdFilter($productIds)
            ->addStoreFilter(Store::DEFAULT_STORE_ID);

        $productCollection->addMediaGalleryData();
        $productCollection->addTierPriceData();

        $products = [];
        /** @var $product Product */
        foreach ($productCollection as $product) {
            $products[] = $product;
        }
        $this->setAllowProducts($products);

        return $this->getData('allow_products');
    }

    /**
     * Retrieve configurable attributes data.
     *
     * @param array $attributeIds
     *
     * @return array
     */
    private function getConfigurableAttributes(array $attributeIds): array
    {
        $requiredAttributes = [
            'name',
            'price',
            'weight',
            'image',
            'thumbnail',
            'status',
            'visibility',
            'media_gallery'
        ];

        $filter = $this->filterBuilder
            ->setField(AttributeInterface::ATTRIBUTE_ID)
            ->setConditionType('in')
            ->setValue($attributeIds)
            ->create();
        $this->searchCriteriaBuilder->addFilters([$filter]);
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $optionAttributes = $this->productAttributeRepository->getList($searchCriteria)->getItems();
        $usedAttributes = array_map(fn($option) => $option->getAttributeCode(), $optionAttributes);

        return array_unique(array_merge($requiredAttributes, $usedAttributes));
    }

    /**
     * @inheritdoc
     */
    public function getJsonSwatchConfig(): string
    {
        if (!$this->useDefaultRenderer()) {
            return parent::getJsonSwatchConfig();
        }

        $product = $this->getProduct();
        $attributesData = $product->getTypeInstance()->getUsedProductAttributes($product) ?: [];

        $config = [];
        /** @var AttributeInterface $attribute */
        foreach ($attributesData as $attribute) {
            $attributeId = (int)$attribute->getAttributeId();
            if ($attribute->getOptions()) {
                $attributeDataArray = $attribute->getData();
                $options = [];
                foreach ($attribute->getOptions() as $attributeOption) {
                    if (!$attributeOption->getValue()) {
                        continue;
                    }
                    $options[$attributeOption->getValue()] = $attributeOption->getLabel();
                }
                $allOptionIds = $this->getOptionsIds($attributeId);
                $swatchesData = $this->swatchHelper->getSwatchesByOptionsId($allOptionIds);
                $attributeDataArray['options'] = $options;
                $config[$attributeId] = $this->addSwatchDataForAttribute(
                    $options,
                    $swatchesData,
                    $attributeDataArray
                );
            }
            if ($attribute->getData('additional_data')) {
                $config[$attributeId]['additional_data'] = $attribute->getData('additional_data');
            }
        }
        return $this->jsonEncoder->encode($config);
    }

    /**
     * Get configurable options ids.
     */
    private function getOptionsIds(int $attributeId): array
    {
        $ids = [];
        $product = $this->getProduct();
        $attributes = $product->getTypeInstance()->getUsedProductAttributes($product) ?: [];
        foreach ($this->getAllowProducts() as $allowProduct) {
            foreach ($attributes as $attribute) {
                $productAttributeId = (int)$attribute->getId();
                if ($productAttributeId == $attributeId) {
                    $ids[$allowProduct->getData($attribute->getAttributeCode())] = 1;
                }
            }
        }
        return array_keys($ids);
    }

    /**
     * @inheritdoc
     */
    protected function getRendererTemplate(): string
    {
        return $this->isProductHasSwatchAttribute() ?
            self::SWATCH_RENDERER_TEMPLATE : self::CONFIGURABLE_RENDERER_TEMPLATE;
    }

    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->setCacheKey('branch8_marketplace_configurable_product_swatch_renderer' . $this->getProduct()->getId());
        parent::_construct();
    }
}
