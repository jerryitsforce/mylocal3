<?php

namespace Branch8\Catalog\Plugin\Magento\CatalogDataExporter\Model\Provider;

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
        $result[] = $this->getCreatedAtTimeStampConfig();
        $result[] = $this->getTotalSaleProductConfig();
        return $result;
    }

    /**
     * @return array
     */
    private function getCreatedAtTimeStampConfig()
    {
        return [
            'id' => '65001',
            'storeCode' => self::DEFAULT_STORE_CODE,
            'websiteCode' => self::DEFAULT_BASE_WEBSITE_CODE,
            'storeViewCode' => self::DEBFAULT_STOREVIEW_CODE,
            'attributeCode' => 'created_at_timestamp',
            'attributeType' => 'catalog_product',
            'dataType' => 'int',
            'multi' => false,
            'label' => __('Created At(timestamp)'),
            'frontendInput' => 'text',
            'required' => false,
            'unique' => false,
            'global' => true,
            'visible' => true,
            'searchable' => false,
            'filterable' => false,
            'visibleInCompareList' => true,
            'visibleInListing' => true,
            'sortable' => true,
            'visibleInSearch' => true,
            'filterableInSearch' => true,
            'searchWeight' => 1.0,
            'usedForRules' => true,
            'boolean' => false,
            "systemAttribute" => true,
            'numeric' => true,
        ];
    }

    /**
     * @return array
     */
    private function getTotalSaleProductConfig()
    {
        return [
            'id' => '65002',
            'storeCode' => self::DEFAULT_STORE_CODE,
            'websiteCode' => self::DEFAULT_BASE_WEBSITE_CODE,
            'storeViewCode' => self::DEBFAULT_STOREVIEW_CODE,
            'attributeCode' => 'total_sale',
            'attributeType' => 'catalog_product',
            'dataType' => 'int',
            'multi' => false,
            'label' => __('熱門商品'),
            'frontendInput' => 'text',
            'required' => false,
            'unique' => false,
            'global' => true,
            'visible' => true,
            'searchable' => false,
            'filterable' => false,
            'visibleInCompareList' => true,
            'visibleInListing' => true,
            'sortable' => false,
            'visibleInSearch' => true,
            'filterableInSearch' => true,
            'searchWeight' => 1.0,
            'usedForRules' => true,
            'boolean' => false,
            "systemAttribute" => true,
            'numeric' => true,
        ];
    }
}
