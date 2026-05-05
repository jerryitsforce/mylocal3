<?php

namespace Branch8\DataFeedManager\Plugin\Wyomind\DataFeedManager\Block\Adminhtml\Feeds\Edit\Tab;

class FiltersPlugin
{
    /**
     * @return false|string
     */
    public function aroundGetJsData(\Wyomind\DataFeedManager\Block\Adminhtml\Feeds\Edit\Tab\Filters $subject, $proceed, ...$args)
    {
        $ignoreAttributes = ['index_seller_id','qty'];
        $attributeCodes = [];
        $attributeList = $subject->getAttributesList();
        foreach ($attributeList as $attribute) {
            if (in_array($attribute['attribute_code'], $ignoreAttributes)) {
                continue;
            }
            if (preg_match("/^[a-zA-Z0-9_]+\$/", $attribute['attribute_code'])) {
                if (isset($attribute['attribute_id'])) {
                    $attributeOptions = $subject->getAttributeOptions($attribute['attribute_id']);
                    $options = [];
                    if (is_array($attributeOptions)) {
                        foreach ($attributeOptions as $attributeOption) {
                            if (!is_null($attributeOption['value'])) {
                                $options[] = ['value' => isset($attributeOption['option_id']) ? $attributeOption['option_id'] : $attributeOption['value'], 'label' => isset($attributeOption['label']) ? $attributeOption['label'] : $attributeOption['value']];
                            }
                        }
                    }
                    if ($attribute['attribute_code'] != 'location') {
                        $attributeCodes[$attribute['attribute_code']] = $options;
                    }
                }
            }
        }
        return json_encode($attributeCodes);
    }
}
