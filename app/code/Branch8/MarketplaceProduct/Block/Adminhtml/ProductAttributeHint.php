<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Block\Adminhtml;

use Branch8\MarketplaceProduct\Model\Source\ProductAttributeHint as ProductAttributeOptions;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;

/**
 * HTML select element block.
 *
 * @method setName(string $value)
 */
class ProductAttributeHint extends Select
{
    /**
     * @var ProductAttributeOptions
     */
    private ProductAttributeOptions $productAttributeOptions;

    /**
     * ProductAttribute constructor.
     *
     * @param Context $context
     * @param ProductAttributeOptions $productAttributeOptions
     * @param array $data
     */
    public function __construct(
        Context                 $context,
        ProductAttributeOptions $productAttributeOptions,
        array                   $data = []
    ) {
        parent::__construct($context, $data);
        $this->productAttributeOptions = $productAttributeOptions;
    }

    /**
     * Sets name for input element.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setInputName(string $value): self
    {
        return $this->setName($value);
    }

    /**
     * Sets ID for input element.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setInputId(string $value): self
    {
        return $this->setId($value);
    }

    /**
     * @inheritDoc
     */
    protected function _toHtml(): string // @codingStandardsIgnoreLine - required by parent class
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->productAttributeOptions->toOptionArray());
        }
        $this->setExtraParams('style="width:150px;"');
        return parent::_toHtml();
    }
}
