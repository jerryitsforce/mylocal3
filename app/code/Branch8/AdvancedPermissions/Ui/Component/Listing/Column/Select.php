<?php

declare(strict_types=1);

namespace Branch8\AdvancedPermissions\Ui\Component\Listing\Column;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Select extends Column
{
    /** @var OptionSourceInterface */
    protected OptionSourceInterface $option;

    /** @var string */
    protected string $defaultLabel;

    protected $resourceConnection;

    protected $coreRegistry;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param OptionSourceInterface $option
     * @param array $components
     * @param array $data
     * @param string $defaultLabel
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OptionSourceInterface $option,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Registry $coreRegistry,
        array $components,
        array $data,
        string $defaultLabel = ''
    ) {
        $this->option = $option;
        $this->defaultLabel = $defaultLabel;
        $this->resourceConnection = $resourceConnection;
        $this->coreRegistry = $coreRegistry;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $connection = $this->resourceConnection->getConnection();
            $resource = $this->resourceConnection;

            $name = $this->getData('name');
            $options = $this->option->toOptionArray();
            $options = array_combine(array_column($options, 'value'), array_column($options, 'label'));
            foreach ($dataSource['data']['items'] as &$item) {
                $salespersonNames = [];
                if (!isset($item[$name])) {
                    // Guard against missing identifiers to avoid undefined index warnings
                    $orderId = isset($item['entity_id']) ? (int)$item['entity_id'] : (int)($item['order_id'] ?? 0);
                    if (!$orderId) {
                        continue;
                    }
                    if ($name === 'salesperson') {
                        $item[$name] = '';

                        try {
                            if($this->coreRegistry->registry('order_seller') === NULL){
                                $allItems = $dataSource['data']['items'];
                                $allOrderIDs = array_column($allItems, 'entity_id');
    
                                $mpTable = $connection->getTableName('marketplace_orders');
                                $allOrderSellers = $connection->fetchAll(
                                    $connection->select()
                                        ->from($mpTable, ['order_id', 'seller_id'])
                                        ->where('order_id IN (?)', $allOrderIDs)
                                );
                                foreach($allOrderSellers as $_orderSeller){
                                    $orderSellerRelate[$_orderSeller['order_id']] = $_orderSeller['seller_id'];
                                }
                                $this->coreRegistry->register('order_seller', $orderSellerRelate);
                            }else{
                                $orderSellerRelate = $this->coreRegistry->registry('order_seller');
                            }

                            $sellerId = (int)(isset($orderSellerRelate[$orderId]) ? $orderSellerRelate[$orderId] : NULL);
                            // If not found → fallback to marketplace_userdata
                            if ($sellerId) {
                                if($this->coreRegistry->registry('seller_salesperson') === NULL){

                                    $mpUserTable = $resource->getTableName('marketplace_userdata');
                                    $salespersons = $connection->fetchAll(
                                        $connection->select()
                                            ->from($mpUserTable, ['seller_id', 'salesperson'])
                                            ->where('seller_id IN (?)', $orderSellerRelate)
                                    );
                                    foreach($salespersons as $_salePersion){
                                        $sellerSalespersonRelate[$_salePersion['seller_id']] = $_salePersion['salesperson'];
                                    }
                                    $this->coreRegistry->register('seller_salesperson', $sellerSalespersonRelate);
                                }else{
                                    $sellerSalespersonRelate = $this->coreRegistry->registry('seller_salesperson');
                                }
                                $salespersonNames[] = $sellerSalespersonRelate[$sellerId];

                            }
                        } catch (\Exception $e) {
                        }
                    }
                    if ($name == 'product_type' && !isset($item[$name])) {
                        $item[$name] = '';

                        try {
                            $soiTable = $resource->getTableName('sales_order_item');
                            $productTypes = $connection->fetchRow(
                                $connection->select()
                                    ->from($soiTable,
                                        [
                                            'product_type' => "GROUP_CONCAT(DISTINCT product_type SEPARATOR '<br>') AS product_type",
                                            'product_name' => "GROUP_CONCAT(DISTINCT name SEPARATOR '<br>') AS product_name",
                                            'supplier_sku' => "GROUP_CONCAT(DISTINCT option_sku SEPARATOR '<br>') AS supplier_sku",
                                            'item_trans_sn' => "GROUP_CONCAT(DISTINCT hotai_point_deduction_point_trans_s_n SEPARATOR '<br>') AS item_trans_sn"
                                        ]
                                    )
                                    ->where('order_id = ?', $orderId)
                                    ->group('order_id')
                            );

                            if ($productTypes) {
                                $item[$name] = $productTypes['product_type'];
                                $item['product_name'] = $productTypes['product_name'];
                                $item['supplier_sku'] = $productTypes['supplier_sku'];
                                $item['item_trans_sn'] = $productTypes['item_trans_sn'];
                            }
                        } catch (\Exception $e) {
                        }
                    }
                }
                if (!empty($salespersonNames)) {
                    foreach ($salespersonNames as $nameItem) {
                        if ($nameItem) {
                            $item[$name] = $item[$name] ? $item[$name] . ', ' : '';
                            $item[$name] .= $options[$nameItem] ?? ($this->defaultLabel ? __($this->defaultLabel) : $nameItem);
                        }
                    }
                } else {
                    $item[$name] = $options[$item[$name]] ?? ($this->defaultLabel ? __($this->defaultLabel) : $item[$name]);
                }
            }
        }
        return $dataSource;
    }
}
