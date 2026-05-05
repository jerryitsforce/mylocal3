<?php

namespace Branch8\ProductAlert\Plugin\Plugin\Magento\CatalogDataExporter\Model\Provider\Product;

use Magento\CatalogDataExporter\Model\Provider\ProductMetadata;

class ProductMetadataPlugin
{
    const DEFAULT_STORE_CODE = 'main_website_store';
    const DEFAULT_BASE_WEBSITE_CODE = 'base';
    const DEBFAULT_STOREVIEW_CODE = 'tw_zh';

    /**
     * @param ProductMetadata $subject
     * @param array $result
     * @param $arguments
     * @return array
     */
    public function afterGet(ProductMetadata $subject, array $result, $arguments): array
    {
        $result[] = $this->getProductIdConfig();
        return $result;
    }

    /**
     * @return array
     */
    public function getProductIdConfig() : array
    {
        return [
            'id' => '65000',
            'storeCode' => self::DEFAULT_STORE_CODE,
            'websiteCode' => self::DEFAULT_BASE_WEBSITE_CODE,
            'storeViewCode' => self::DEBFAULT_STOREVIEW_CODE,
            'attributeCode' => 'mage_id',
            'attributeType' => 'catalog_product',
            'dataType' => 'int',
            'multi' => false,
            'label' => __('Mage Id'),
            'frontendInput' => 'text',
            'required' => false,
            'unique' => false,
            'global' => true,
            'visible' => true,
            'searchable' => true,
            'filterable' => true,
            'visibleInCompareList' => true,
            'visibleInListing' => true,
            'sortable' => false,
            'visibleInSearch' => true,
            'filterableInSearch' => true,
            'searchWeight' => 1.0,
            'usedForRules' => true,
            'boolean' => false,
            "systemAttribute" => true,
            "numeric" => false,
        ];
    }
}
