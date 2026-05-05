<?php

declare(strict_types=1);

namespace Branch8\RoleDelegate\Ui\Component\Listing\Columns;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Actions extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
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

    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if ($item['status'] == 'pending') {
                    $editUrlPath = $this->getData('config/editUrlPath') ?: '#';
                    $item[$this->getData('name')]['edit'] = [
                        'href' => $this->urlBuilder->getUrl($editUrlPath, ['delegate_id' => $item['id'], 'user_id' => $item['user_id']]),
                        'label' => __('Edit')
                    ];
                }
                if ($item['status'] == 'pending' || $item['status'] == 'active') {
                    $cancelUrlPath = $this->getData('config/cancelUrlPath') ?: '#';
                    $item[$this->getData('name')]['cancel'] = [
                        'href' => $this->urlBuilder->getUrl(
                            $cancelUrlPath,
                            [
                                'id' => $item['id']
                            ]
                        ),
                        'label' => __('Cancel'),
                        'confirm' => [
                            'title' => __('Cancel Role Delegate'),
                            'message' => __(
                                'Are you sure you want to cancel an role delegate "%1"?',
                                $item['id']
                            )
                        ],
                        'hidden' => false,
                        'post' => true
                    ];
                }
            }
        }

        return $dataSource;
    }
}
