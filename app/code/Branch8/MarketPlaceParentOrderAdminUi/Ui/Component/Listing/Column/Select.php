<?php

declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Ui\Component\Listing\Column;

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
        array $components,
        array $data,
        string $defaultLabel = ''
    ) {
        $this->option = $option;
        $this->defaultLabel = $defaultLabel;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $name = $this->getData('name');
            $options = $this->option->toOptionArray();
            $options = array_combine(array_column($options, 'value'), array_column($options, 'label'));
            foreach ($dataSource['data']['items'] as &$item) {
                $values = [];
                if (!isset($item[$name])  && isset($item['entity_id'])) {
                    $pOrderId = (int)$item['entity_id'];
                    if ($name === 'salesperson') {
                        try {
                            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                            $resource = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
                            $connection = $resource->getConnection();
                            $spocTable = $connection->getTableName('sales_parent_order_children');
                            $orderIds = $connection->fetchCol(
                                $connection->select()
                                    ->from($spocTable, ['children_id'])
                                    ->where('parent_id = ?', $pOrderId)
                            );

                            if ($orderIds) {
                                // First try marketplace_orders
                                $mpOrderTable = $resource->getTableName('marketplace_orders');
                                $sellerId = $connection->fetchCol(
                                    $connection->select()
                                        ->from($mpOrderTable, ['seller_id'])
                                        ->where('order_id IN (?)', $orderIds)
                                        ->group('seller_id')
                                );

                                // If not found → fallback to marketplace_userdata
                                if ($sellerId) {
                                    foreach ($sellerId as $id) {
                                        $mpUserTable = $resource->getTableName('marketplace_userdata');
                                        $values[] = $connection->fetchOne(
                                            $connection->select()
                                                ->from($mpUserTable, ['salesperson'])
                                                ->where('seller_id = ?', $id)
                                                ->limit(1)
                                        );
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                        }
                    }
                    if ($name == 'product_type' || $name == 'product_name' || $name == 'product_sku') {
                        try {
                            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                            $resource = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
                            $connection = $resource->getConnection();
                            $spocTable = $connection->getTableName('sales_parent_order_children');
                            $orderIds = $connection->fetchCol(
                                $connection->select()
                                    ->from($spocTable, ['children_id'])
                                    ->where('parent_id = ?', $pOrderId)
                            );

                            if ($orderIds) {
                                $soiTable = $resource->getTableName('sales_order_item');
                                $itemData = $connection->fetchAll(
                                    $connection->select()
                                        ->from($soiTable,
                                            [
                                                'product_sku' => "GROUP_CONCAT(DISTINCT sku SEPARATOR '<br>')",
                                                'product_name' => "GROUP_CONCAT(DISTINCT name SEPARATOR '<br>')",
                                                'product_type' => "GROUP_CONCAT(DISTINCT product_type SEPARATOR '|')"
                                            ]
                                        )
                                        ->where('order_id IN (?)', $orderIds)
                                );

                                if (!empty($itemData)) {
                                    if ($name == 'product_type') {
                                        $productTypes = !empty($itemData[0]['product_type']) ? explode('|', $itemData[0]['product_type']) : [];
                                        foreach ($productTypes as $type) {
                                            $values[] = $options[$type] ?? ($this->defaultLabel ? __($this->defaultLabel) : $type);
                                        }
                                    } else {
                                        $item['product_type'] = $itemData[0]['product_type'] ?? '';;
                                    }
                                    $item['product_sku'] = $itemData[0]['product_sku'] ?? '';
                                    $item['product_name'] = $itemData[0]['product_name'] ?? '';
                                }
                            }
                        } catch (\Exception $e) {
                        }
                    }
                    if ($name == 'shipping_method') {
                        try {
                            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                            $resource = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
                            $connection = $resource->getConnection();
                            $spocTable = $connection->getTableName('sales_parent_order_children');
                            $orderIds = $connection->fetchCol(
                                $connection->select()
                                    ->from($spocTable, ['children_id'])
                                    ->where('parent_id = ?', $pOrderId)
                            );

                            if ($orderIds) {
                                $soTable = $resource->getTableName('sales_order');
                                $itemData = $connection->fetchAll(
                                    $connection->select()
                                        ->from($soTable,
                                            [
                                                'shipping_method' => "GROUP_CONCAT(DISTINCT IFNULL(shipping_method, '-') SEPARATOR '|')"
                                            ]
                                        )
                                        ->where('entity_id IN (?)', $orderIds)
                                );

                                if (!empty($itemData)) {
                                    $shippingMethods = !empty($itemData[0]['shipping_method']) ? explode('|', $itemData[0]['shipping_method']) : [];
                                    foreach ($shippingMethods as $shippingMethod) {
                                        $values[] = $options[$shippingMethod] ?? ($this->defaultLabel ? __($this->defaultLabel) : $shippingMethod);
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                        }
                    }
                } else {
                    foreach (($item[$name] ? explode('|', $item[$name]) : []) as $value) {
                        $values[] = $options[$value] ?? ($this->defaultLabel ? __($this->defaultLabel) : $value);
                    }
                }
                if (!in_array($name, ['product_sku', 'product_name'])) {
                    $item[$name] = join('<br>', $values);
                }
            }
        }
        return $dataSource;
    }
}
