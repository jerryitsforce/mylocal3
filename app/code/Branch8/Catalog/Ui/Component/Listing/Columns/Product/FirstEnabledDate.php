<?php
namespace Branch8\Catalog\Ui\Component\Listing\Columns\Product;

use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class FirstEnabledDate extends Column
{
    protected $timezone;

    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        TimezoneInterface $timezone,
        array $components = [],
        array $data = []
    ) {
        $this->timezone = $timezone;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['first_enabled_date'])) {
                    $item['first_enabled_date'] = $this->timezone->date($item['first_enabled_date'])->format('Y-m-d H:i:s');
                }
            }
        }
        return $dataSource;
    }
}
