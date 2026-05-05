<?php

namespace Branch8\DataFeedManager\Rewrite\Block\Adminhtml\Feeds\Renderer;

use Magento\Framework\DataObject;

class Action extends \Wyomind\DataFeedManager\Block\Adminhtml\Feeds\Renderer\Action
{
    /**
     * @inheritDoc
     */
    public function render(DataObject $row)
    {
        $actions = [[
            // Edit
            'caption' => __('Edit'),
            'url' => ['base' => '*/*/edit'],
            'field' => 'id',
        ], [
            // Generate
            'caption' => __('Generate'),
            'url' => "javascript:void(require(['dfm_index'], function (index) { index.generate('" . $this->getUrl('datafeedmanager/feeds/generate', ['id' => $row->getId()]) . "'); }))",
        ], [
            // Preview
            'caption' => __('Preview (%1 items)', $this->_framework->getDefaultConfig("datafeedmanager/system/preview")),
            'url' => ['base' => '*/*/preview'],
            'field' => 'id',
            'popup' => true,
        ], [
            'caption' => __('Download'),
            'url' => $this->getUrl('datafeedmanager/feeds/download', ['id' => $row->getId()]),
            'target' => '_blank',
        ], [
            // Delete
            'caption' => __('Delete'),
            'url' => "javascript:void(require(['dfm_index'], function (index) { index.delete('" . $this->getUrl('datafeedmanager/feeds/delete', ['id' => $row->getId()]) . "'); }))",
        ]];
        $this->getColumn()->setActions($actions);
        return \Magento\Backend\Block\Widget\Grid\Column\Renderer\Action::render($row);
    }
}
