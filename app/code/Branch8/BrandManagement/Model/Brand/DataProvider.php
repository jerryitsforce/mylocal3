<?php
namespace Branch8\BrandManagement\Model\Brand;

use Amasty\ShopbyBase\Api\Data\OptionSettingInterface;
use Branch8\BrandManagement\Api\BrandOptionRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Amasty\ShopbyBase\Model\OptionSettingRepository;
use Amasty\ShopbyBase\Model\OptionSettings\UrlResolver;
use Psr\Log\LoggerInterface;

class DataProvider extends AbstractDataProvider
{
    protected $loadedData;
    protected $brandOptionRepository;
    protected $request;
    protected $optionSettingRepository;
    protected $urlResolver;
    protected $storeManager;
    protected LoggerInterface $logger;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        BrandOptionRepositoryInterface $brandOptionRepository,
        RequestInterface $request,
        OptionSettingRepository $optionSettingRepository,
        UrlResolver $urlResolver,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        array $meta = [],
        array $data = []
    ) {
        $this->brandOptionRepository = $brandOptionRepository;
        $this->request = $request;
        $this->optionSettingRepository = $optionSettingRepository;
        $this->urlResolver = $urlResolver;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @inheritdoc
     *
     * Magento_Ui js/form/element/abstract setInitialValue sets isUseDefault from disabled().
     * Catalog Eav modifier sets config.disabled when attribute uses default at store — same pattern here.
     */
    public function getMeta()
    {
        $meta = parent::getMeta();
        $storeId = (int) $this->request->getParam('store', Store::DEFAULT_STORE_ID);
        $optionId = (int) $this->request->getParam('id');

        if ($storeId < Store::DEFAULT_STORE_ID || !$optionId) {
            return $meta;
        }

        $flags = $this->getStoreViewUseDefaultFlags($optionId, $storeId);

        return array_replace_recursive($meta, [
            'meta_data_fieldset' => [
                'children' => [
                    'meta_title' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'disabled' => $flags['meta_title'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'base' => [
                'children' => [
                    'title' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'disabled' => $flags['title'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $optionId = $this->request->getParam('id');
        $storeId = (int) $this->request->getParam('store', 0);

        if ($optionId) {
            try {
                $brandOption = $this->brandOptionRepository->getBrandOptionById($optionId);
                $optionSetting = $this->getOptionSetting($optionId, $storeId);

                if ($brandOption) {
                    $data = [
                        'option_id' => $brandOption['option_id'],
                        'value' => $brandOption['value'],
                        'sort_order' => $brandOption['sort_order'],
                        'url_alias' => $optionSetting->getUrlAlias() ?? '',
                        'meta_title' => $optionSetting->getMetaTitle() ?? '',
                        'meta_description' => $optionSetting->getMetaDescription() ?? '',
                        'meta_keywords' => $optionSetting->getMetaKeywords() ?? '',
                        'title' => $optionSetting->getTitle() ?? '',
                        'description' => $optionSetting->getDescription() ?? '',
                        'short_description' => $optionSetting->getShortDescription() ?? '',
                        'top_cms_block_id' => $optionSetting->getTopCmsBlockId() ?? '',
                        'bottom_cms_block_id' => $optionSetting->getBottomCmsBlockId() ?? '',
                        'image' => $this->formatImageData($optionSetting, 'image'),
                        'slider_image' => $this->formatImageData($optionSetting, 'slider_image'),
                        'image_alt' => $optionSetting->getImageAlt() ?? '',
                        'small_image_alt' => $optionSetting->getSmallImageAlt() ?? '',
                        'is_featured' => (int)($optionSetting->getIsFeatured() ?? 0),
                        'is_show_in_widget' => (int)($optionSetting->getIsShowInWidget() ?? 1),
                        'is_show_in_slider' => (int)($optionSetting->getIsShowInSlider() ?? 0),
                        'slider_position' => $optionSetting->getSliderPosition() ?? 0,
                        'show_brand_info' => $optionSetting->isShowBrandInfo() ?? false,
                        'brand_info_block_position' => $optionSetting->getBrandInfoBlockPosition() ?? '',
                        'brand_info_postal_address' => $optionSetting->getBrandInfoPostalAddress() ?? '',
                        'brand_info_electronic_address' => $optionSetting->getBrandInfoElectronicAddress() ?? '',
                        'brand_info_contact' => $optionSetting->getBrandInfoContact() ?? '',
                        'page_layout' => $optionSetting->getPageLayout() ?? '',
                        'en_alphabet' => $optionSetting->getData('en_alphabet') ?? '',
                        'ch_alphabet' => $optionSetting->getData('ch_alphabet') ?? ''
                    ];

                    if ($storeId > Store::DEFAULT_STORE_ID) {
                        $flags = $this->getStoreViewUseDefaultFlagsFromModel($optionSetting, $storeId);
                        $data['meta_title_use_default'] = $flags['meta_title'];
                        $data['title_use_default'] = $flags['title'];
                        $data['use_default']['meta_title'] = $flags['meta_title'] ? 1 : 0;
                        $data['use_default']['title'] = $flags['title'] ? 1 : 0;
                    }

                    $this->loadedData = [$optionId => $data];
                } else {
                    $this->loadedData = [];
                }
            } catch (\Exception $e) {
                $this->logger->error($e);
                $this->loadedData = [];
            }
        } else {
            $this->loadedData = [];
        }

        return $this->loadedData;
    }

    private function getOptionSetting($optionId, $storeId = 0)
    {
        try {
            return $this->optionSettingRepository->getByCode('brand', $optionId, $storeId);
        } catch (\Exception $e) {
            $this->logger->error($e);
            return new \Amasty\ShopbyBase\Model\OptionSetting();
        }
    }

    /**
     * @return array{meta_title: bool, title: bool}
     */
    private function getStoreViewUseDefaultFlags(int $optionId, int $storeId): array
    {
        return $this->getStoreViewUseDefaultFlagsFromModel($this->getOptionSetting($optionId, $storeId), $storeId);
    }

    /**
     * Amasty OptionSettingRepository sets {field}_use_default when loading store views.
     * If both flags are missing while the loaded row is the default store row, treat as full inherit (no store row yet).
     *
     * @return array{meta_title: bool, title: bool}
     */
    private function getStoreViewUseDefaultFlagsFromModel(OptionSettingInterface $optionSetting, int $requestedStoreId): array
    {
        $meta = $optionSetting->getData('meta_title_use_default');
        $title = $optionSetting->getData('title_use_default');
        if ($meta === null && $title === null
            && $requestedStoreId > Store::DEFAULT_STORE_ID
            && (int) $optionSetting->getStoreId() === Store::DEFAULT_STORE_ID
        ) {
            return ['meta_title' => true, 'title' => true];
        }

        return [
            'meta_title' => $meta === null ? true : (bool) $meta,
            'title' => $title === null ? true : (bool) $title,
        ];
    }

    public function addFilter(\Magento\Framework\Api\Filter $filter)
    {
        // Form data provider: filtering not used
    }

    public function getCollection()
    {
        return new \Magento\Framework\Data\Collection();
    }

    private function formatImageData($optionSetting, $fieldName)
    {
        $imageFileName = $fieldName === 'image'
            ? $optionSetting->getImage()
            : $optionSetting->getSliderImage();

        if (empty($imageFileName)) {
            return [];
        }

        $imageUrl = $fieldName === 'image'
            ? $this->urlResolver->resolveImageUrl($optionSetting)
            : $this->urlResolver->resolveSliderImageUrl($optionSetting, true);

        if (empty($imageUrl) && !empty($imageFileName)) {
            $baseMediaUrl = rtrim(
                $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA),
                '/'
            );

            if ($fieldName === 'slider_image') {
                $imagePath = 'amasty/shopby/option_images/slider/' . $imageFileName;
            } else {
                $imagePath = 'amasty/shopby/option_images/' . $imageFileName;
            }

            $imageUrl = $baseMediaUrl . '/' . $imagePath;
        }

        if (empty($imageUrl)) {
            return [];
        }

        return [
            [
                'file' => $imageFileName,
                'url' => $imageUrl,
                'name' => $imageFileName
            ]
        ];
    }
}
