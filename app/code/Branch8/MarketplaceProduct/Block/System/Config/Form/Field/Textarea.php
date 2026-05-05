<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Block\System\Config\Form\Field;

use Magento\Framework\View\Element\Template;

/**
 * Backend system config array field renderer.
 */
class Textarea extends Template
{
    /**
     * @return string
     */
    public function _toHtml()
    {
        $inputName = $this->getInputName();
        $column = $this->getColumn();

        return '<textarea id="' . $this->getInputId().'" name="' . $inputName . '" ' .
            ($column['size'] ? 'size="' . $column['size'] . '"' : '') . ' class="' .
            (isset($column['class']) ? $column['class'] : 'input-text') . '"'.
            (isset($column['style']) ? ' style="'.$column['style'] . '"' : '') . '></textarea>';
    }
}
