<?php

namespace Branch8\Brand\Plugin;

use Amasty\ShopbyBase\Api\Data\OptionSettingInterface;
use Amasty\ShopbyBase\Model\OptionSettingFactory;
use Amasty\ShopbyBrand\Helper\Data as DataHelper;
use Amasty\ShopbyBrand\Model\Attribute;
use Amasty\ShopbyBrand\Model\Brand\BrandDataInterface;
use Amasty\ShopbyBrand\Model\BrandSettingProvider;
use Amasty\ShopbyBrand\Model\ProductCount;
use Magento\Eav\Model\Entity\Attribute\Option;
use Magento\Framework\App\Cache\Type\Collection as CollectionCache;
use Magento\Framework\Serialize\SerializerInterface;

class BrandLoadItems
{
    /**
     * @var ProductCount
     */
    private $productCount;

    /**
     * @var BrandSettingProvider
     */
    private $brandSettingProvider;

    /**
     * @var OptionSettingFactory
     */
    private $optionSettingFactory;

    /**
     * @var Attribute
     */
    private $brandAttribute;

    /**
     * @var CollectionCache
     */
    private $cache;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var int
     */
    private $cacheLifetime;

    /**
     * @var DataHelper
     */
    private $helper;

    public function __construct(
        ProductCount $productCount,
        BrandSettingProvider $brandSettingProvider,
        OptionSettingFactory $optionSettingFactory,
        Attribute $brandAttribute,
        CollectionCache $cache,
        SerializerInterface $serializer,
        DataHelper $helper,
        ?int $cacheLifetime = 86400
    ) {
        $this->productCount = $productCount;
        $this->brandSettingProvider = $brandSettingProvider;
        $this->optionSettingFactory = $optionSettingFactory;
        $this->brandAttribute = $brandAttribute;
        $this->cache = $cache;
        $this->serializer = $serializer;
        $this->cacheLifetime = $cacheLifetime;
        $this->helper = $helper;
    }

    /**
     * @param $subject
     * @param \Closure $proceed
     * @param int $storeId
     * @param int|null $customerGroupId
     * @return array
     */
    public function aroundGetData($subject, \Closure $proceed, int $storeId, ?int $customerGroupId = null): array
    {
        $identifier = $subject->getCacheKey($storeId, $customerGroupId);
        $data = $this->cache->load($identifier);
        if ($data !== false) {
            return $this->serializer->unserialize($data);
        }

        $options = $this->brandAttribute->getOptions($storeId);
        if ($options === null) {
            return [];
        }

        $data = [];

        foreach ($options as $option) {
            $optionValue = (int) $option->getValue();
            $setting = $this->brandSettingProvider->getItemByStoreIdAndValue($storeId, $optionValue)
                ?? $this->optionSettingFactory->create();

            $data[] = $this->extractData($option, $setting);
        }

        $this->cache->save(
            $this->serializer->serialize($data),
            $identifier,
            $subject->getCacheTags(),
            $this->cacheLifetime
        );

        return $data;
    }

    /**
     * @param Option $option
     * @param OptionSettingInterface $setting
     *
     * @return array
     */
    private function extractData(Option $option, OptionSettingInterface $setting): array
    {
        return [
            OptionSettingInterface::IS_FEATURED => $setting->getIsFeatured(),
            OptionSettingInterface::META_TITLE => $setting->getMetaTitle(),
            OptionSettingInterface::META_DESCRIPTION => $setting->getMetaDescription(),
            OptionSettingInterface::META_KEYWORDS => $setting->getMetaKeywords(),
            OptionSettingInterface::TOP_CMS_BLOCK_ID => $setting->getTopCmsBlockId(),
            OptionSettingInterface::BOTTOM_CMS_BLOCK_ID => $setting->getBottomCmsBlockId(),
            BrandDataInterface::IS_SHOW_IN_WIDGET => $setting->getIsShowInWidget(),
            BrandDataInterface::IS_SHOW_IN_SLIDER => $setting->getIsShowInSlider(),
            BrandDataInterface::BRAND_ID => $option->getValue(),
            BrandDataInterface::LABEL => trim((string) ($setting->getLabel() ? : $option->getLabel())),
            BrandDataInterface::URL => $this->helper->getBrandUrl($option),
            BrandDataInterface::IMG => $setting->getSliderImageUrl(),
            BrandDataInterface::IMAGE => $setting->getImageUrl(),
            BrandDataInterface::ALT => $setting->getSmallImageAlt() ? : $setting->getLabel(),
            BrandDataInterface::DESCRIPTION => $setting->getDescription(true),
            BrandDataInterface::SHORT_DESCRIPTION => $setting->getShortDescription(),
            BrandDataInterface::COUNT => $this->productCount->get($setting->getValue()),
            BrandDataInterface::POSITION => $setting->getSliderPosition(),
            'en_alphabet' => $setting->getEnAlphabet(),
            'ch_alphabet' => $setting->getChAlphabet(),
        ];
    }
}
