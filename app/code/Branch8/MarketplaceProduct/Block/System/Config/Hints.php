<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Block\System\Config;

use Branch8\MarketplaceProduct\Block\Adminhtml\ProductAttributeHint;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Branch8\MarketplaceProduct\Block\System\Config\Form\Field\Textarea;

/**
 * Backend system config array field renderer.
 */
class Hints extends AbstractFieldArray
{
    /**
     * @var ProductAttributeHint|null
     */
    private ?ProductAttributeHint $productAttribute = null;

    /**
     * @var Textarea|null
     */
    private ?Textarea $textAreaType = null;

    /**
     * @inheritdoc
     *
     * @throws LocalizedException
     */
    protected function _prepareToRender(): void // @codingStandardsIgnoreLine - required by parent class
    {
        $this->addColumn(
            'attribute',
            [
                'label' => __('Attribute'),
                'class' => 'required-entry',
                'renderer' => $this->getProductAttributeRenderer()
            ]
        );
        $this->addColumn(
            'hint',
            [
                'label' => __('Hint'),
                'class' => 'required-entry',
                'style' => 'width: 400px;',
                'renderer' => $this->getTextAreaTypes()
            ]
        );
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * Retrieve product attribute renderer.
     *
     * @return ProductAttributeHint
     *
     * @throws LocalizedException
     */
    protected function getProductAttributeRenderer(): ProductAttributeHint
    {
        if (!$this->productAttribute) {
            $this->productAttribute = $this->getLayout()->createBlock(
                ProductAttributeHint::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
            $this->productAttribute->setClass('required-entry');
        }
        return $this->productAttribute;
    }

    /**
     * Retrieve textarea renderer.
     *
     * @return Textarea
     */
    private function getTextAreaTypes()
    {
        if (!$this->textAreaType) {
            $this->textAreaType = $this->getLayout()->createBlock(
                Textarea::class,
                ''
            );
        }

        return $this->textAreaType;
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
