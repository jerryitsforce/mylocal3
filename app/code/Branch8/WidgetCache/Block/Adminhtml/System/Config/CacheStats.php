<?php

namespace Branch8\WidgetCache\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Branch8\WidgetCache\Helper\Data as WidgetCacheHelper;

class CacheStats extends Field
{
	/** @var WidgetCacheHelper */
	private $widgetCacheHelper;

	public function __construct(
		Context $context,
		WidgetCacheHelper $widgetCacheHelper,
		array $data = []
	) {
		$this->widgetCacheHelper = $widgetCacheHelper;
		parent::__construct($context, $data);
	}

	protected function _getElementHtml(AbstractElement $element)
	{
		$stats = $this->widgetCacheHelper->getCacheStatistics();

		$rows = [];
		foreach ($stats as $key => $value) {
			$label = ucwords(str_replace('_', ' ', $key));
			$display = is_bool($value) ? ($value ? 'Yes' : 'No') : (string)$value;
			$rows[] = sprintf('<tr><th style="text-align:left;padding:4px 8px;">%s</th><td style="padding:4px 8px;">%s</td></tr>', $label, $display);
		}

		$html = '<table class="admin__table-secondary">' . implode('', $rows) . '</table>';
		return $html;
	}
}
