<?php

namespace Branch8\SalesOrderGrid\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Sales\Model\Order\ItemRepository;
use Magento\Ui\Component\Listing\Columns\Column;
use Branch8\SalesOrderGrid\Helper\Logger as CustomLogger;
use Webkul\Marketplace\Model\ResourceModel\Saleslist\CollectionFactory;

class ProductSku extends Column
{
    protected $_conn;

    private $logger;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param CustomLogger $logger
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface                          $context,
        UiComponentFactory                        $uiComponentFactory,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        CustomLogger                              $logger,
        array                                     $components = [],
        array                                     $data = []
    )
    {
        $this->logger = $logger;
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
                    $item['product_sku'] = implode('<br/><br/>', $result);
                } catch (\Exception $e) {
                    $this->logger->critical($e->getMessage());
                    $this->logger->info('Row:' . print_r($item, true));
                }
            }
        }
        return $dataSource;
    }
}
