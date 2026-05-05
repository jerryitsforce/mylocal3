<?php

declare(strict_types=1);

namespace Branch8\GiftToFriend\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class ProductName extends Column
{
    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $name = $this->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                if (!isset($item[$name])) {
                    $pOrderId = (int)$item['entity_id'];
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
                            $itemData = $connection->fetchCol(
                                $connection->select()
                                    ->from($soiTable,
                                        [
                                            'product_name' => "GROUP_CONCAT(DISTINCT name SEPARATOR '<br>')"
                                        ]
                                    )
                                    ->where('order_id IN (?)', $orderIds)
                            );

                            if (!empty($itemData)) {
                                $item[$name] = $itemData[0] ?? '';
                            }
                        }
                    } catch (\Exception $e) {
                    }
                }
            }
        }
        return $dataSource;
    }
}
