<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Branch8\BrandManagement\Observer\Admin;

use Amasty\ShopbyBase\Api\Data\OptionSettingInterface;
use Amasty\ShopbyBase\Model\OptionSetting;
use Amasty\ShopbyBase\Model\OptionSettings\Save;
use Amasty\ShopbyBase\Model\OptionSettings\UrlResolver;
use Amasty\ShopbyBrand\Model\ConfigProvider;
use Magento\Catalog\Model\Category\Attribute\Source\Page;
use Magento\Cms\Model\Wysiwyg\Config;
use Magento\Config\Model\Config\Source\Yesno as YesNoSource;
use Magento\Framework\Data\Form;
use Magento\Framework\Data\Form\Element\Fieldset;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class OptionFormBuildAfter implements ObserverInterface
{
    private const FIELDSET_META_DATA = 'meta_data_fieldset';
    private const FIELDSET_PRODUCT_LIST = 'product_list_fieldset';
    private const FIELDSET_OTHER = 'other_fieldset';

    /**
     * @var Page
     */
    private $page;

    /**
     * @var Config
     */
    private $wysiwygConfig;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var UrlResolver
     */
    private $optionsUrlResolver;

    /**
     * @var YesNoSource
     */
    private $yesnoSource;
    private LoggerInterface $logger;

    /**
     * Constructor
     *
     * @param Page $page
     * @param ConfigProvider $configProvider
     * @param Config $wysiwygConfig
     * @param UrlResolver $optionsUrlResolver
     * @param YesNoSource $yesnoSource
     * @param LoggerInterface $logger
     */
    public function __construct(
        Page $page,
        ConfigProvider $configProvider,
        Config $wysiwygConfig,
        UrlResolver $optionsUrlResolver,
        YesNoSource $yesnoSource,
        LoggerInterface $logger
    ) {
        $this->page = $page;
        $this->wysiwygConfig = $wysiwygConfig;
        $this->configProvider = $configProvider;
        $this->optionsUrlResolver = $optionsUrlResolver;
        $this->yesnoSource = $yesnoSource;
        $this->logger = $logger;
    }

    /**
     * Execute observer
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var Form $form */
        $form = $observer->getData('form');
        /** @var OptionSetting $setting */
        $setting = $observer->getData('setting');
        $storeId = (int) ($observer->getData('store_id') ?? 0);
        $attributeCode = $setting->getAttributeCode();
        $isBrandAttributeCode = $attributeCode === $this->configProvider->getBrandAttributeCode($storeId);

        $this->addMetaDataFieldset($form);
        $this->addProductListFieldset($form, $setting, $storeId, $isBrandAttributeCode);
        $this->addOtherFieldset($observer, $isBrandAttributeCode);
    }

    /**
     * Add Meta Data Fieldset
     * Skip adding fields as Amasty observer already adds them
     *
     * @param Form $form
     * @return void
     */
    private function addMetaDataFieldset(Form $form): void
    {
        $existingFieldset = $form->getElement(self::FIELDSET_META_DATA);
        if ($existingFieldset) {
            return; // Amasty observer already added all fields
        }

        $fieldset = $this->getOrCreateFieldset($form, self::FIELDSET_META_DATA, [
            'legend' => __('Meta Data'),
            'class' => 'form-inline'
        ]);

        $this->safeAddField($fieldset, 'meta_title', 'text', [
            'name' => 'meta_title',
            'label' => __('Meta Title'),
            'title' => __('Meta Title')
        ]);

        $this->safeAddField($fieldset, 'meta_description', 'textarea', [
            'name' => 'meta_description',
            'label' => __('Meta Description'),
            'title' => __('Meta Description')
        ]);

        $this->safeAddField($fieldset, 'meta_keywords', 'textarea', [
            'name' => 'meta_keywords',
            'label' => __('Meta Keywords'),
            'title' => __('Meta Keywords')
        ]);
    }

    /**
     * Add Product List Fieldset
     *
     * @param Form $form
     * @param OptionSetting $model
     * @param int $storeId
     * @param bool $isBrandAttributeCode
     * @return void
     */
    private function addProductListFieldset(
        Form $form,
        OptionSetting $model,
        int $storeId,
        bool $isBrandAttributeCode
    ): void {
        $fieldset = $this->getOrCreateFieldset($form, self::FIELDSET_PRODUCT_LIST, [
            'legend' => __('Page Content'),
            'class' => 'form-inline'
        ]);

        $this->addBasicFields($fieldset);
        $this->addImageField($fieldset, $model, $storeId);

        if ($isBrandAttributeCode) {
            $this->addBrandSpecificFields($fieldset);
        }

        $this->addCmsBlockFields($fieldset);
        $this->addBrandInfoField($fieldset);
    }

    /**
     * Add basic fields (title, description)
     *
     * @param Fieldset $fieldset
     * @return void
     */
    private function addBasicFields(Fieldset $fieldset): void
    {
        $this->safeAddField($fieldset, 'title', 'text', [
            'name' => 'title',
            'label' => __('Page Title'),
            'title' => __('Title')
        ]);

        $this->safeAddField($fieldset, 'description', 'editor', [
            'name' => 'description',
            'label' => __('Description'),
            'title' => __('Description'),
            'wysiwyg' => true,
            'config' => $this->wysiwygConfig->getConfig(['add_variables' => false]),
        ]);
    }

    /**
     * Add image field with delete option
     *
     * @param Fieldset $fieldset
     * @param OptionSetting $model
     * @param int $storeId
     * @return void
     */
    private function addImageField(Fieldset $fieldset, OptionSetting $model, int $storeId): void
    {
        $categoryImage = $this->buildImageDeleteHtml($model, $storeId);

        $this->safeAddField($fieldset, 'image', 'file', [
            'name' => 'image',
            'label' => __('Image'),
            'title' => __('Image'),
            'after_element_html' => $categoryImage
        ]);
    }

    /**
     * Add brand-specific fields
     *
     * @param Fieldset $fieldset
     * @return void
     */
    private function addBrandSpecificFields(Fieldset $fieldset): void
    {
        $this->safeAddField($fieldset, 'short_description', 'textarea', [
            'name' => 'short_description',
            'label' => __('Short Description'),
            'title' => __('Short Description')
        ], 'description');

        $this->safeAddField($fieldset, OptionSettingInterface::IMAGE_ALT, 'text', [
            'name' => OptionSettingInterface::IMAGE_ALT,
            'label' => __('Image Alt'),
            'title' => __('Image Alt'),
            'note' => __('Image Alt will be used for the brand image on the brand page')
        ], 'image');
    }

    /**
     * Add CMS block fields
     *
     * @param Fieldset $fieldset
     * @return void
     */
    private function addCmsBlockFields(Fieldset $fieldset): void
    {
        $listCmsBlocks = $this->page->toOptionArray();

        $this->safeAddField($fieldset, 'top_cms_block_id', 'select', [
            'name' => 'top_cms_block_id',
            'label' => __('Top CMS Block'),
            'title' => __('Top CMS Block'),
            'values' => $listCmsBlocks
        ]);

        $this->safeAddField($fieldset, 'bottom_cms_block_id', 'select', [
            'name' => 'bottom_cms_block_id',
            'label' => __('Bottom CMS Block'),
            'title' => __('Bottom CMS Block'),
            'values' => $listCmsBlocks
        ]);
    }

    /**
     * Add brand info field
     *
     * @param Fieldset $fieldset
     * @return void
     */
    private function addBrandInfoField(Fieldset $fieldset): void
    {
        $this->safeAddField($fieldset, OptionSettingInterface::SHOW_BRAND_INFO, 'select', [
            'name' => OptionSettingInterface::SHOW_BRAND_INFO,
            'label' => __('Display Additional Brand Information'),
            'title' => __('Display Additional Brand Information'),
            'values' => $this->yesnoSource->toOptionArray(),
            'disabled' => true,
            'note' => $this->getBrandInfoNote()
        ], OptionSettingInterface::BOTTOM_CMS_BLOCK_ID);
    }

    /**
     * Add Other Fieldset
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @param bool $isBrandAttributeCode
     * @return void
     */
    private function addOtherFieldset(\Magento\Framework\Event\Observer $observer, bool $isBrandAttributeCode): void
    {
        /** @var OptionSetting $model */
        $model = $observer->getData('setting');
        /** @var Form $form */
        $form = $observer->getData('form');

        $fieldset = $this->getOrCreateFieldset($form, self::FIELDSET_OTHER, [
            'legend' => __('Other'),
            'class' => 'form-inline'
        ]);

        $sliderImage = $this->buildSliderImageDeleteHtml($model);
        $note = $this->getSliderImageNote($isBrandAttributeCode);
        $smallImageAltNote = $this->getSmallImageAltNote($isBrandAttributeCode);

        $this->safeAddField($fieldset, 'slider_image', 'file', [
            'name' => 'slider_image',
            'label' => __('Small Image'),
            'title' => __('Small Image'),
            'note' => $note,
            'after_element_html' => $sliderImage
        ]);

        $this->safeAddField($fieldset, OptionSettingInterface::SMALL_IMAGE_ALT, 'text', [
            'name' => OptionSettingInterface::SMALL_IMAGE_ALT,
            'label' => __('Small Image Alt'),
            'title' => __('Small Image Alt'),
            'note' => $smallImageAltNote
        ]);
    }

    /**
     * Get or create fieldset
     *
     * @param Form $form
     * @param string $id
     * @param array $config
     * @return Fieldset
     */
    private function getOrCreateFieldset(Form $form, string $id, array $config): Fieldset
    {
        $existingFieldset = $form->getElement($id);
        if ($existingFieldset instanceof Fieldset) {
            return $existingFieldset;
        }

        return $form->addFieldset($id, $config);
    }

    /**
     * Safely add field to fieldset with error handling
     *
     * @param Fieldset $fieldset
     * @param string $fieldId
     * @param string $fieldType
     * @param array $config
     * @param string|null $afterFieldId
     * @return void
     */
    private function safeAddField(
        Fieldset $fieldset,
        string $fieldId,
        string $fieldType,
        array $config,
        ?string $afterFieldId = null
    ): void {
        try {
            if ($fieldset->getElement($fieldId)) {
                return; // Field already exists
            }

            if ($afterFieldId) {
                $fieldset->addField($fieldId, $fieldType, $config, $afterFieldId);
            } else {
                $fieldset->addField($fieldId, $fieldType, $config);
            }
        } catch (\Exception $e) {
            $this->logger->error($e);
            // Field already exists or other error, skip silently
        }
    }

    /**
     * Build image delete HTML
     *
     * @param OptionSetting $model
     * @param int $storeId
     * @return string
     */
    private function buildImageDeleteHtml(OptionSetting $model, int $storeId): string
    {
        $url = $this->optionsUrlResolver->resolveImageUrl($model);
        if (!$url) {
            return '';
        }

        $imageUseDefault = $model->getData('image_use_default') && $model->getCurrentStoreId();
        $disabled = $imageUseDefault ? 'disabled="disabled"' : '';
        $hiddenStyle = $imageUseDefault ? 'style="display:none"' : '';

        return sprintf(
            '<div><br>
            <input type="checkbox" id="image_delete" name="%s" value="1" %s />
            <label for="image_delete">%s</label>
            <br>
            <br><img src="%s" %s alt="Current Image"/>
            </div>',
            Save::IMAGE_DELETE,
            $disabled,
            __('Delete Image'),
            $url,
            $hiddenStyle
        );
    }

    /**
     * Build slider image delete HTML
     *
     * @param OptionSetting $model
     * @return string
     */
    private function buildSliderImageDeleteHtml(OptionSetting $model): string
    {
        $img = $this->optionsUrlResolver->resolveSliderImageUrl($model, true);
        if (!$img) {
            return '';
        }

        $imageUseDefault = $model->getData('slider_image_use_default') && $model->getCurrentStoreId();
        $disabled = $imageUseDefault ? 'disabled="disabled"' : '';
        $hiddenStyle = $imageUseDefault ? 'display:none;' : '';

        return sprintf(
            '<div><br>
            <input type="checkbox" id="slider_image_delete" name="%s" value="1" %s />
            <label for="slider_image_delete">%s</label>
            <br><br>
            <img src="%s" alt="Current Image" style="%s"/>
            </div>',
            Save::SLIDER_IMAGE_DELETE,
            $disabled,
            __('Delete Image'),
            $img,
            $hiddenStyle
        );
    }

    /**
     * Get slider image note
     *
     * @param bool $isBrandAttributeCode
     * @return \Magento\Framework\Phrase
     */
    private function getSliderImageNote(bool $isBrandAttributeCode): \Magento\Framework\Phrase
    {
        return $isBrandAttributeCode
            ? __('Used in Brands Slider, Product Page Icon & Swatch for Multiselect Attribute.')
            : __('Used as Product Page Icon & Swatch for Multiselect Attribute.');
    }

    /**
     * Get small image alt note
     *
     * @param bool $isBrandAttributeCode
     * @return \Magento\Framework\Phrase
     */
    private function getSmallImageAltNote(bool $isBrandAttributeCode): \Magento\Framework\Phrase
    {
        return $isBrandAttributeCode
            ? __('Small Image Alt will be used for the brand image in brand pop-up, brand slider, on all brands and product pages.')
            : __('Small Image Alt will be used for the Product Page Icon Image & Swatch Image for Multiselect Attribute.');
    }

    /**
     * Get brand info note
     *
     * @return string
     */
    private function getBrandInfoNote(): string
    {
        return sprintf(
            'The functionality is available as part of an active product subscription or support '
            . 'subscription. To upgrade and obtain functionality please follow the '
            . '<a href="%s" target="_blank">link</a>.<br>'
            . 'Than you can find the \'%s\' package for installation in composer suggest.',
            'https://amasty.com/amcustomer/account/products/'
                . '?utm_source=extension&utm_medium=backend&utm_campaign=subscribe_shopbybrand',
            'amasty/module-shop-by-brand-subscription-functionality'
        );
    }
}
