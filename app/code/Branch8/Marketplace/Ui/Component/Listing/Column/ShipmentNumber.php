<?php

namespace Branch8\Marketplace\Ui\Component\Listing\Column;

use Branch8\Marketplace\Helper\Data as MarketplaceHelper;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class ShipmentNumber extends Column
{
    /**
     * @var MarketplaceHelper
     */
    protected $marketplaceHelper;

    /**
     * Constructor.
     *
     * @param ContextInterface   $context
     * @param UiComponentFactory $uiComponentFactory
     * @param MarketplaceHelper $marketplaceHelper
     * @param array              $components
     * @param array              $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        MarketplaceHelper $marketplaceHelper,
        array $components = [],
        array $data = []
    ) {
        $this->marketplaceHelper = $marketplaceHelper;
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
            foreach ($dataSource['data']['items'] as &$item) {
                $item['track_number'] = $this->marketplaceHelper->getShipmentInfoByOrderId($item['order_id'], 'track_number');
            }
        }

        return $dataSource;
    }
}
