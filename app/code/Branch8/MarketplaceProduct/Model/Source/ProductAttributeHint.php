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
class ProductAttributeHint implements OptionSourceInterface
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
                'label' => $attribute->getDefaultFrontendLabel(),
                'value' => $attribute->getAttributeCode()
            ];
        }
        $options[] = [
            'label' => __('Quantity'),
            'value' => 'stock'
        ];
        $options[] = [
            'label' => __('Stock Status'),
            'value' => 'is_in_stock'
        ];
        $options[] = [
            'label' => __('Customizable Options'),
            'value' => 'custom_option'
        ];
        $options[] = [
            'label' => __('Related Products'),
            'value' => 'related_skus'
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
