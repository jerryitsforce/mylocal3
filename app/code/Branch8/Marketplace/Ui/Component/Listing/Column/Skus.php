<?php

namespace Branch8\Marketplace\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Sales\Model\Order\ItemRepository;
use Magento\Ui\Component\Listing\Columns\Column;
use Webkul\Marketplace\Model\ResourceModel\Saleslist\CollectionFactory;
use Branch8\Marketplace\Service\MarketplaceLogger;
use Magento\Framework\App\ObjectManager;

class Skus extends Column
{
    protected $_conn;

    /**
     * @var MarketplaceLogger
     */
    private MarketplaceLogger $marketplaceLogger;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param MarketplaceLogger|null $marketplaceLogger
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface                          $context,
        UiComponentFactory                        $uiComponentFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        MarketplaceLogger                         $marketplaceLogger = null,
        array                                     $components = [],
        array                                     $data = []
    )
    {
        $this->marketplaceLogger = $marketplaceLogger
            ?: ObjectManager::getInstance()->get(MarketplaceLogger::class);
        $this->_conn = $resourceConnection->getConnection();
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (!isset($item['all_item_skus']) || (string)$item['all_item_skus'] == '') {
                    return $dataSource;
                }
                try {
                    $allSkus = explode('||', $item['all_item_skus']);
                    if(empty($allSkus)) {
                        return $dataSource;
                    }
                    $result = [];
                    foreach ($allSkus as $skuString) {
                        $parts = explode(':', $skuString);
                        if (isset($parts[1])) {
                            $result[] = $parts[1];
                        }
                    }
                    if($result){
                        $item['magepro_sku'] = implode('<br/><br/>', $result);
                    }
                } catch (\Exception $e) {
                    $this->marketplaceLogger->logException('Skus', $e, ['row' => $item]);
                }
            }
        }
        return $dataSource;
    }
}
