<?php
/**
 * Copyright © OpenGento, All rights reserved.
 * See LICENSE bundled with this library for license details.
 */
declare(strict_types=1);

namespace Opengento\CategoryImportExport\Model\Config\Source\Category;

use Magento\Catalog\Model\Category;
use Magento\Eav\Model\Attribute;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Exception\LocalizedException;
use Opengento\CategoryImportExport\Model\Config\ExcludedFields;

use function sprintf;

class Attributes implements OptionSourceInterface
{
    private ?array $options = null;

    public function __construct(
        private Config $config,
        private CollectionFactory $collectionFactory,
        private ExcludedFields $excludedFields
    ) {}

    /**
     * @throws LocalizedException
     */
    public function toOptionArray(): array
    {
        return $this->options ??= $this->resolveAttributes();
    }

    /**
     * @throws LocalizedException
     */
    private function resolveAttributes(): array
    {
        $options = [];
        /** @var Attribute $attribute */
        foreach ($this->createAttributeCollection()->getItems() as $attribute) {
            $options[] = [
                'label' => sprintf('%s (%s)', $attribute->getDefaultFrontendLabel(), $attribute->getAttributeCode()),
                'value' => $attribute->getAttributeCode()
            ];
        }

        return $options;
    }

    /**
     * @throws LocalizedException
     */
    private function createAttributeCollection(): Collection
    {
        $collection = $this->collectionFactory->create();
        $collection->setEntityTypeFilter($this->config->getEntityType(Category::ENTITY));
        $collection->addFieldToSelect(['attribute_code', 'frontend_label']);
        $collection->addFieldToFilter('attribute_code', ['nin' => $this->excludedFields->get()]);
        $collection->setOrder('frontend_label', 'ASC');
        $collection->setOrder('attribute_code', 'ASC');

        return $collection;
    }
}
