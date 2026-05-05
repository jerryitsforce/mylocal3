<?php

namespace Branch8\HifiSalesReport\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Branch8\HifiSalesReport\Helper\Common as CommonHelper;

class DownloadCheckFile extends Column
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
                        'check_file_1'          => [
                            'href'   => $this->getLinkUrlForColumn($item['record_id'], "CheckFile1"),
                            'target' => '_blank',
                            'label'  => __('Check File 1')
                        ],
                        'check_file_2'          => [
                            'href'   => $this->getLinkUrlForColumn($item['record_id'], "CheckFile2"),
                            'target' => '_blank',
                            'label'  => __('Check File 2')
                        ],
                        'check_file_3'          => [
                            'href'   => $this->getLinkUrlForColumn($item['record_id'], "CheckFile3"),
                            'target' => '_blank',
                            'label'  => __('Check File 3')
                        ],
                        // for test
                        'money_diff_checker'    => [
                            'href'   => $this->urlBuilder->getUrl(
                                "hifi_sales_report/display/MoneyDiffChecker",
                                ['record_id' => $item['record_id']]
                            ),
                            'target' => '_blank',
                            'label'  => 'Money Diff Checker'
                        ],
                        'order_missing_checker' => [
                            'href'   => $this->urlBuilder->getUrl(
                                "hifi_sales_report/display/OrderMissingChecker",
                                ['record_id' => $item['record_id']]
                            ),
                            'target' => '_blank',
                            'label'  => 'Order Missing Checker'
                        ],
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
    protected function getLinkUrlForColumn(int $record_id, string $checkFileLastRoute): string
    {
        return $this->urlBuilder->getUrl(
            "hifi_sales_report/download/{$checkFileLastRoute}",
            ['record_id' => $record_id]
        );
    }
}
