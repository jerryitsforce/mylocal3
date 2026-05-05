<?php

declare(strict_types=1);

namespace Branch8\Catalog\Ui\Component\Product\Form\Brand;

use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Psr\Log\LoggerInterface;

class Options implements OptionSourceInterface
{
    /**
     * @var string
     */
    private const LOG_PREFIX = 'Branch8_Catalog::BrandOptions';

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var ProductAttributeRepositoryInterface
     */
    private ProductAttributeRepositoryInterface $productAttributeRepository;

    /**
     * Options constructor.
     *
     * @param LoggerInterface $logger
     * @param ProductAttributeRepositoryInterface $productAttributeRepository
     */
    public function __construct(
        LoggerInterface                     $logger,
        ProductAttributeRepositoryInterface $productAttributeRepository
    ) {
        $this->logger = $logger;
        $this->productAttributeRepository = $productAttributeRepository;
    }

    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        try {
            $attribute = $this->productAttributeRepository->get('brand');

            $options = $attribute->getOptions();

            $optionData = [];
            foreach ($options as $option) {
                $optionData[] = ['value' => $option->getValue(), 'label' => $option->getLabel()];
            }

            return $optionData;
        } catch (\Exception $e) {
            $this->logger->error(self::LOG_PREFIX, ['exception' => 'Attribute "brand" not found.']);
            return [];
        }
    }
}
