<?php

namespace Branch8\HifiSalesReport\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Branch8\HifiSalesReport\Model\HifiSalesReportRecordRepository;
use Magento\User\Model\UserFactory;

class ShowCreatorAdmin extends Column
{
    /** @var UrlInterface */
    protected $urlBuilder;

    /** @var HifiSalesReportRecordRepository */
    protected $mainRecordRepository;

    /** @var UserFactory */
    protected $userFactory;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        HifiSalesReportRecordRepository $mainRecordRepository,
        UserFactory $userFactory,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder           = $urlBuilder;
        $this->mainRecordRepository = $mainRecordRepository;
        $this->userFactory          = $userFactory;

        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['record_id'])) {
                    $item[$this->getData('name')] = [
                        'url' => [
                            'href'   => $this->getAdminUrl($item['record_id']),
                            'target' => '_blank',
                            'label'  => $this->getAdminName($item['record_id'])
                        ]
                    ];
                }
            }
        }

        return $dataSource;
    }

    /**
     * 根據傳入的結帳報表紀錄ID回傳管理者名稱
     *
     * @param integer $record_id
     * @return string
     */
    protected function getAdminName(int $record_id): string
    {
        $record = $this->mainRecordRepository->get($record_id);

        $user = $this->userFactory->create()->load($record->getCreatorAdminId());

        return "{$user->getLastName()} {$user->getFirstName()}";
    }

    /**
     * 根據傳入的結帳報表紀錄ID回傳管理者資訊網址
     *
     * @param integer $record_id
     * @return string
     */
    protected function getAdminUrl(int $record_id): string
    {
        $record = $this->mainRecordRepository->get($record_id);

        $user = $this->userFactory->create()->load($record->getCreatorAdminId());

        return $this->urlBuilder->getUrl(
            "adminhtml/user/edit",
            ['user_id' => $user->getId()]
        );
    }
}
