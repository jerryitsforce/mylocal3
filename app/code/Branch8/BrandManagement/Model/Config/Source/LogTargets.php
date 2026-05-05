<?php

namespace Branch8\BrandManagement\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class LogTargets implements OptionSourceInterface
{
    /**
     * Provide selectable log targets (per-class buckets) for the admin configuration field.
     *
     * @return array<int, array{value: string, label: \Magento\Framework\Phrase}>
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'ImageUploader', 'label' => __('ImageUploader (var/log/BrandManagement/ImageUploader/Y_m_d.log)')],
            ['value' => 'BrandOptionRepository', 'label' => __('BrandOptionRepository (var/log/BrandManagement/BrandOptionRepository/Y_m_d.log)')],

            ['value' => 'Controller_Save', 'label' => __('Controller_Save (var/log/BrandManagement/Controller_Save/Y_m_d.log)')],
            ['value' => 'Controller_Delete', 'label' => __('Controller_Delete (var/log/BrandManagement/Controller_Delete/Y_m_d.log)')],
            ['value' => 'Controller_MassDelete', 'label' => __('Controller_MassDelete (var/log/BrandManagement/Controller_MassDelete/Y_m_d.log)')],
            ['value' => 'Controller_InlineEdit', 'label' => __('Controller_InlineEdit (var/log/BrandManagement/Controller_InlineEdit/Y_m_d.log)')],
            ['value' => 'Controller_ImageUpload', 'label' => __('Controller_ImageUpload (var/log/BrandManagement/Controller_ImageUpload/Y_m_d.log)')],
            ['value' => 'Controller_SliderImageUploader', 'label' => __('Controller_SliderImageUploader (var/log/BrandManagement/Controller_SliderImageUploader/Y_m_d.log)')],
            ['value' => 'Controller_Edit', 'label' => __('Controller_Edit (var/log/BrandManagement/Controller_Edit/Y_m_d.log)')],

            ['value' => 'BrandDataProvider_Form', 'label' => __('BrandDataProvider_Form (var/log/BrandManagement/BrandDataProvider_Form/Y_m_d.log)')],
            ['value' => 'BrandDataProvider_Listing', 'label' => __('BrandDataProvider_Listing (var/log/BrandManagement/BrandDataProvider_Listing/Y_m_d.log)')],

            ['value' => 'Plugin_AddUrlToResponse', 'label' => __('Plugin_AddUrlToResponse (var/log/BrandManagement/Plugin_AddUrlToResponse/Y_m_d.log)')],
            ['value' => 'Observer_OptionFormBuildAfter', 'label' => __('Observer_OptionFormBuildAfter (var/log/BrandManagement/Observer_OptionFormBuildAfter/Y_m_d.log)')],
            ['value' => 'Source_CmsBlock', 'label' => __('Source_CmsBlock (var/log/BrandManagement/Source_CmsBlock/Y_m_d.log)')],
        ];
    }
}

