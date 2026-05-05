<?php

namespace Branch8\GeneralNonNotifyTicket\Ui\Component\Listing\Columns;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class DeleteUnsold extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');

            foreach ($dataSource['data']['items'] as &$item) {
                $item[$fieldName]["delete_unsold"] = [
                    "href"    => $this->getContext()->getUrl(
                        "GeneralNonNotifyTicket/DeleteUnsold/ReceiveGridForm",
                        [
                            "batch_setting_id" => $item["batch_setting_id"],
                            "product_id"       => $item["belong_to_product_id"],
                        ]
                    ),
                    "label"   => __('Delete unsold tickets'),
                    'confirm' => [
                        'title'   => __('Confirmation.'),
                        'message' => __('Are you sure you want to delete unsold tickets under batch code: ' . $item['batch_code'])
                    ]
                ];
            }
        }

        return $dataSource;
    }
}
