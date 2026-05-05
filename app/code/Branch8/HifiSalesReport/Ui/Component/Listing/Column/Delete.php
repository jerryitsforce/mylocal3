<?php

namespace Branch8\HifiSalesReport\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Branch8\HifiSalesReport\Model\HifiSalesReportRecordRepository;
use Branch8\HifiSalesReport\Helper\Common as CommonHelper;

class Delete extends Column
{
    /** @var UrlInterface */
    protected $urlBuilder;

    /** @var HifiSalesReportRecordRepository */
    protected $hifiSalesReportRecordRepository;

    /** @var CommonHelper */
    protected $commonHelper;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        HifiSalesReportRecordRepository $hifiSalesReportRecordRepository,
        CommonHelper $commonHelper,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder                      = $urlBuilder;
        $this->hifiSalesReportRecordRepository = $hifiSalesReportRecordRepository;
        $this->commonHelper                    = $commonHelper;

        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['record_id']) && $this->commonHelper->checkIfMainRecordAllowDelete($item['record_id'])) {
                    $item[$this->getData('name')] = [
                        'preview' => [
                            'href'    => $this->getLinkUrlForColumn($item['record_id']),
                            'target'  => '_blank',
                            'label'   => __('Delete'),
                            'confirm' => [
                                'title'   => __('Confirmation.'),
                                'message' => __('Are you sure you want to delete this record?')
                            ]
                        ]
                    ];
                }
            }
        }

        return $dataSource;
    }

    /**
     * 根據傳入的結帳報表紀錄ID回傳刪除地址
     *
     * @param integer $record_id
     * @return string
     */
    protected function getLinkUrlForColumn(int $record_id): string
    {
        return $this->urlBuilder->getUrl(
            'hifi_sales_report/delete/index',
            ['record_id' => $record_id]
        );
    }
}
