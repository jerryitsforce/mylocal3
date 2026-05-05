<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       29/03/2026
 */

namespace Branch8\HotaiCore\Block\Adminhtml\Form\Field\WhiteListModuleLog;
class LogLevels extends \Magento\Framework\View\Element\Text
{
    const LEVELS = [
        ["label" => "Critical", "value" => "critical"],
        ["label" => "Error", "value" => "error"],
        ["label" => "Warning", "value" => "warning"],
        ["label" => "Info", "value" => "info"],
        ["label" => "Debug", "value" => "debug"]
    ];

    protected function _toHtml()
    {
        $inputName = $this->getInputName();
        $column = $this->getColumn();
        $columnName = $this->getColumnName();
        $inputId = $this->getInputId();
        //$all = json_encode(self::LEVELS);
        $scriptString = <<<HTML
    <%
        var all=window.allLogLevels;
        var levels = ($columnName || '').split(',').map(l => l.trim());
    %>

  <% _.each(all, function(level) { %>
    <label style="margin-right:10px;">
        <input type="checkbox"
               name="log_levels[]"
               value="<%- level.value %>"
               data-input-reference="$inputId"
               <%= levels.includes(level.value) ? 'checked' : '' %> />
        <%- level.label %>
    </label>
<% }); %>
HTML;
        return $scriptString . '
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
