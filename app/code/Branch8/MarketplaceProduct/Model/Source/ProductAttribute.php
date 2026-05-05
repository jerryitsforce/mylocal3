<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model\Source;

use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\Api\Search\SearchCriteriaFactory;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Configuration options for product attribute.
 */
class ProductAttribute implements OptionSourceInterface
{
    /**
     * @var SearchCriteriaFactory
     */
    private SearchCriteriaFactory $searchCriteriaFactory;

    /**
     * @var AttributeRepositoryInterface
     */
    private AttributeRepositoryInterface $attributeRepository;

    /**
     * ProductAttribute constructor.
     *
     * @param SearchCriteriaFactory $searchCriteriaFactory
     * @param AttributeRepositoryInterface $attributeRepository
     */
    public function __construct(
        SearchCriteriaFactory        $searchCriteriaFactory,
        AttributeRepositoryInterface $attributeRepository
    ) {
        $this->searchCriteriaFactory = $searchCriteriaFactory;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        $options = [];

        $attributes = $this->getAttributes();
        /** @var AbstractAttribute $attribute */
        foreach ($attributes as $attribute) {
            $options[] = [
                'label' => $attribute->getDefaultFrontendLabel() . ' (' . $attribute->getAttributeCode() . ')',
                'value' => $attribute->getAttributeCode()
            ];
        }
        $options[] = [
            'label' => __('Quantity (stock)'),
            'value' => 'stock'
        ];
        $options[] = [
            'label' => __('Stock Status (is_in_stock)'),
            'value' => 'is_in_stock'
        ];
        $options[] = [
            'label' => __('Customizable Options (custom_option)'),
            'value' => 'custom_option'
        ];
        $options[] = [
            'label' => __('Related (related_skus)'),
            'value' => 'related_skus'
        ];
        $options[] = [
            'label' => __('Product Discount Limit (product_discount_limit)'),
            'value' => 'product_discount_limit'
        ];
        $options[] = [
            'label' => __('Redeem Type (redeem_type)'),
            'value' => 'redeem_type'
        ];
        $options[] = [
            'label' => __('Point Value (point_value)'),
            'value' => 'point_value'
        ];
        $options[] = [
            'label' => __('Custom Option Variation (co_variation)'),
            'value' => 'co_variation'
        ];
        $options[] = [
            'label' => __('Images (images)'),
            'value' => 'images'
        ];
        $options[] = [
            'label' => __('Schedule Title (schedule_title)'),
            'value' => 'schedule_title'
        ];
        $options[] = [
            'label' => __('Schedule Start Time (schedule_start_time)'),
            'value' => 'schedule_start_time'
        ];
        $options[] = [
            'label' => __('Schedule End Time (schedule_end_time)'),
            'value' => 'schedule_end_time'
        ];
        $options[] = [
            'label' => __('Option Title {x} (custom_option)'),
            'value' => 'option_title'
        ];
        $options[] = [
            'label' => __('Corresponded Alphabet | Option Name {x} | SKU | Is Visible (custom_option)'),
            'value' => 'option_detail'
        ];
        $options[] = [
            'label' => __('Option Type (custom_option)'),
            'value' => 'option_type'
        ];
        $options[] = [
            'label' => __('Variation SKU (Variation)'),
            'value' => 'variation_sku'
        ];
        $options[] = [
            'label' => __('Salable Qty (Variation)'),
            'value' => 'variation_qty'
        ];
        $options[] = [
            'label' => __('Follow Simple SKU Cost Setting (Variation)'),
            'value' => 'variation_follow_simple_sku_cost'
        ];
        $options[] = [
            'label' => __('Cost Setting (Variation)'),
            'value' => 'variation_cost_setting'
        ];
        $options[] = [
            'label' => __('Commission Rate (Variation)'),
            'value' => 'variation_commission_rate'
        ];
        $options[] = [
            'label' => __('Cost (Variation)'),
            'value' => 'variation_cost'
        ];
        $options[] = [
            'label' => __('Price (Variation)'),
            'value' => 'variation_price'
        ];
        $options[] = [
            'label' => __('Follow Simple SKU Price Setting (Variation)'),
            'value' => 'variation_follow_simple_sku_price'
        ];
        $options[] = [
            'label' => __('Images (Variation)'),
            'value' => 'variation_images'
        ];
        $options[] = [
            'label' => __('Certification (branch8_certifications_post)'),
            'value' => 'branch8_certifications_post'
        ];

        return $options;
    }

    /**
     * Retrieve all attributes for product entity.
     *
     * @return array
     */
    private function getAttributes(): array
    {
        $searchCriteria = $this->searchCriteriaFactory->create();
        return $this->attributeRepository->getList(Product::ENTITY, $searchCriteria)
            ->getItems();
    }
}
