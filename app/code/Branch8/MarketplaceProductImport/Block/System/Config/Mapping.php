<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProductImport\Block\System\Config;

use Branch8\MarketplaceProduct\Block\Adminhtml\ProductAttribute;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

/**
 * Backend system config array field renderer.
 */
class Mapping extends AbstractFieldArray
{
    /**
     * @var ProductAttribute|null
     */
    private ?ProductAttribute $productAttribute = null;

    /**
     * @inheritdoc
     *
     * @throws LocalizedException
     */
    protected function _prepareToRender(): void // @codingStandardsIgnoreLine - required by parent class
    {
        $this->addColumn(
            'title',
            [
                'label' => __('Title'),
                'class' => 'required-entry',
                'style' => 'width: 150px;'
            ]
        );
        $this->addColumn(
            'attribute',
            [
                'label' => __('Attribute'),
                'class' => 'required-entry',
                'renderer' => $this->getProductAttributeRenderer()
            ]
        );
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * Retrieve product attribute renderer.
     *
     * @return ProductAttribute
     *
     * @throws LocalizedException
     */
    protected function getProductAttributeRenderer(): ProductAttribute
    {
        if (!$this->productAttribute) {
            $this->productAttribute = $this->getLayout()->createBlock(
                ProductAttribute::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
            $this->productAttribute->setClass('required-entry');
        }
        return $this->productAttribute;
    }

    /**
     * @inheritdoc
     *
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row): void // @codingStandardsIgnoreLine - required by parent class
    {
        $attribute = $row->getData('value');
        $options = [];
        if ($attribute) {
            $options['option_' . $this->getProductAttributeRenderer()->calcOptionHash($attribute)]
                = 'selected="selected"';
        }
        $row->setData('option_extra_attrs', $options);
    }
}
