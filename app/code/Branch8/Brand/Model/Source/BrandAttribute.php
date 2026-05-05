<?php
namespace Branch8\Brand\Model\Source;

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Amasty\ShopbyBrand\Block\Widget\BrandListAbstract;

/**
 * Class BrandAttribute
 */
class BrandAttribute implements \Magento\Framework\Option\ArrayInterface
{
    /** @var  AttributeRepositoryInterface */
    protected AttributeRepositoryInterface $eavAttribute;

    /** @var  ScopeConfigInterface */
    protected ScopeConfigInterface $scopeConfig;

    public function __construct(
        AttributeRepositoryInterface $eavAttribute,
        ScopeConfigInterface $scopeConfig
    ){
        $this->eavAttribute = $eavAttribute;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        foreach ($this->_getOptions() as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }
        return $options;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return $this->_getOptions();
    }

    /**
     * Retrieve options
     *
     * @return array
     */
    protected function _getOptions()
    {
        $options = [];
        try {
            $brandAttrCode = $this->scopeConfig->getValue(BrandListAbstract::PATH_BRAND_ATTRIBUTE_CODE);
            $brandAttr = $this->eavAttribute->get(\Magento\Catalog\Model\Product::ENTITY, $brandAttrCode);
            $allOptions = $brandAttr->getSource()->getAllOptions();
            foreach ($allOptions as $option) {
                if (empty($option['value']) || empty($option['label'])) {
                    continue;
                }
                $options[$option['value']] = $option['label'];
            }
        } catch (\Exception $e) {
        }

        return $options;
    }
}
