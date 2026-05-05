<?php

namespace Branch8\HifiSalesReport\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class DownloadFilteredDetailFile extends Column
{
    /** @var UrlInterface */
    protected $urlBuilder;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['record_id'])) {
                    $item[$this->getData('name')] = [
                        'download' => [
                            'href'   => $this->getLinkUrlForColumn($item['record_id']),
                            'target' => '_blank',
                            'label'  => __('Download')
                        ]
                    ];
                }
            }
        }

        return $dataSource;
    }

    /**
     * 根據傳入的結帳報表紀錄ID回傳下載地址
     *
     * @param integer $record_id
     * @return string
     */
    protected function getLinkUrlForColumn(int $record_id): string
    {
        return $this->urlBuilder->getUrl(
            'hifi_sales_report/download/filteredDetailFile',
            [
                'record_id' => $record_id
            ]
        );
    }
}
