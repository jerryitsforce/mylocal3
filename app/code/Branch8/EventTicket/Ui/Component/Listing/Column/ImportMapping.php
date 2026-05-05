<?php

namespace Branch8\EventTicket\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class ImportMapping extends Column
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
        ContextInterface   $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface       $urlBuilder,
        array              $components = [],
        array              $data = []
    )
    {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {

        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (!empty($item['entity_id'])) {
                    $popupData = [
                        'id' => $item['entity_id'],
                        'title' => $item['name'],
                        'brand' => $item['ticket_type']
                    ];
                    $item['import_mapping'] = '<a href="#" class="import-mapping-btn" data-mapping=\''.json_encode($popupData).'\'>'.__('Import Mapping').'</a> / 
                    <a href="'.$this->urlBuilder->getUrl('eventticket/ticket/downloadLogMapping', ['poolId' => $item['entity_id']]).'" target="_blank">View log</a>';
                } else {
                    $item['import_mapping'] = '';
                }
            }
        }
        return $dataSource;
    }
}