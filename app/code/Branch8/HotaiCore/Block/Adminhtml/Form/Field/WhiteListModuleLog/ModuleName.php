<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       29/03/2026
 */

namespace Branch8\HotaiCore\Block\Adminhtml\Form\Field\WhiteListModuleLog;
class ModuleName extends \Magento\Framework\View\Element\Text
{
    /**
     * @return string
     */
    protected function _toHtml()
    {
        $inputName = $this->getInputName();
        $column = $this->getColumn();
        $columnName = $this->getColumnName();
        $inputId = $this->getInputId();
        return '
        <label for="' . $inputId . '"><%- ' . $columnName . ' %>
            <input type="hidden" id="' . $inputId .
                '"' .
                ' name="' .
                $inputName .
                '" value="<%- ' .
                $columnName .
                ' %>" ' .
                ($column['size'] ? 'size="' .
                    $column['size'] .
                    '"' : '') .
                ' class="' .
                ($column['class'] ?? 'input-text') . '"' . (isset($column['style']) ? ' style="' . $column['style'] . '"' : '') . '/>
        </label>';
    }
}
