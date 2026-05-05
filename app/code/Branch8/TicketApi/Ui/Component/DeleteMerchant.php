<?php

namespace Branch8\TicketApi\Ui\Component;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class DeleteMerchant extends Column
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
                if (isset($item['entity_id'])) {
                    $item[$this->getData('name')] = [
                        'preview' => [
                            'href'   => $this->getLinkUrlForColumn($item['entity_id']),
                            'target' => '_self',
                            'label'  => __('Delete'),
                            'confirm' => [
                                'title'   => __('Confirmation.'),
                                'message' => __('Are you sure you want to delete this merchant?')
                            ]
                        ]
                    ];
                }
            }
        }

        return $dataSource;
    }

    /**
     * @param integer $id
     * @return string
     */
    protected function getLinkUrlForColumn(int $id): string
    {
        return $this->urlBuilder->getUrl(
            'ticket_api/merchant/delete',
            ['entity_id' => $id]
        );
    }
}
