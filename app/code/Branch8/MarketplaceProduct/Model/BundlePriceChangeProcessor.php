<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Model;

use Branch8\MarketplaceProduct\Model\ResourceModel\MarkPriceChange;
use Magento\Bundle\Model\ProductRelationsProcessorInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Reflection\TypeCaster;

/**
 * Mark price change after handle product relations.
 */
class BundlePriceChangeProcessor implements ProductRelationsProcessorInterface
{
    /**
     * @var TypeCaster
     */
    private TypeCaster $typeCaster;

    /**
     * @var MarkPriceChange
     */
    private MarkPriceChange $markPriceChange;

    /**
     * @var array
     */
    private array $comparisonFieldsTypeMapper;

    /**
     * BundlePriceChangeProcessor constructor.
     *
     * @param TypeCaster $typeCaster
     * @param MarkPriceChange $markPriceChange
     * @param array $comparisonFieldsTypeMapper
     */
    public function __construct(
        TypeCaster      $typeCaster,
        MarkPriceChange $markPriceChange,
        array           $comparisonFieldsTypeMapper = []
    ) {
        $this->typeCaster = $typeCaster;
        $this->markPriceChange = $markPriceChange;
        $this->comparisonFieldsTypeMapper = $comparisonFieldsTypeMapper;
    }

    /**
     * Mark price change if product options or links are changed.
     *
     * @param ProductInterface $product
     * @param array $existingProductOptions
     * @param array $expectedProductOptions
     * @return void
     */
    public function process(
        ProductInterface $product,
        array            $existingProductOptions,
        array            $expectedProductOptions
    ): void {
        if (empty($existingProductOptions)) {
            return;
        }

        if ($this->isProductOptionsChanged($existingProductOptions, $expectedProductOptions)
            || $this->isProductLinksChanged($existingProductOptions, $expectedProductOptions)
        ) {
            $this->markPriceChange->execute([$product->getId()]);
        }
    }

    /**
     * Check product options change.
     *
     * @param array $existingProductOptions
     * @param array $expectedProductOptions
     * @return bool
     */
    private function isProductOptionsChanged(
        array $existingProductOptions,
        array $expectedProductOptions
    ): bool {
        if (count($existingProductOptions) !== count($expectedProductOptions)) {
            return true;
        }

        $productOptionsDiff = array_udiff(
            $expectedProductOptions,
            $existingProductOptions,
            function ($expectedProductOption, $existingProductOption) {
                if ($expectedProductOption->getOptionId() === $existingProductOption->getOptionId()) {
                    return $expectedProductOption->getRequired() - $existingProductOption->getRequired();
                }

                return $expectedProductOption->getOptionId() - $existingProductOption->getOptionId();
            }
        );

        return (bool)count($productOptionsDiff);
    }

    /**
     * Check product links change.
     *
     * @param array $existingProductOptions
     * @param array $expectedProductOptions
     * @return bool
     */
    private function isProductLinksChanged(
        array $existingProductOptions,
        array $expectedProductOptions
    ): bool {
        $existingProductLinks = $this->flattenProductLinksData($existingProductOptions);
        $expectedProductLinks = $this->flattenProductLinksData($expectedProductOptions);

        return $existingProductLinks != $expectedProductLinks;
    }

    /**
     * Simplify product links data.
     *
     * @param array $productOptions
     * @return array
     */
    private function flattenProductLinksData(array $productOptions): array
    {
        return array_reduce($productOptions, function ($result, $productOption) {
            $optionId = $productOption->getOptionId();
            $productLinks = [];
            foreach ($productOption->getProductLinks() as $productLink) {
                $productLinkData = $productLink->getData();
                $productLinkFilteredData = [];
                foreach ($this->comparisonFieldsTypeMapper as $fieldName => $fieldType) {
                    if (isset($productLinkData[$fieldName])) {
                        $productLinkFilteredData[$fieldName] = $this->typeCaster->castValueToType(
                            $productLinkData[$fieldName],
                            $fieldType
                        );
                    }
                }
                $productLinks[$productLink->getId()] = $productLinkFilteredData;
            }
            $result[$optionId] = $productLinks;

            return $result;
        });
    }
}
