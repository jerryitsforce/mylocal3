<?php

declare(strict_types=1);

namespace Branch8\GiftToFriend\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class BuyerPhone extends Column
{
    /**
     * @inheritDoc
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['customer_id'])) {
                    if (!isset($item[$fieldName])) {
                        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                        $resource = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
                        $connection = $resource->getConnection();
                        $customerGrid = $connection->getTableName('customer_grid_flat');

                        $buyerPhone = $connection->fetchOne(
                            $connection->select()
                                ->from($customerGrid, ['phone_number'])
                                ->where('entity_id = ?', $item['customer_id'])
                                ->limit(1)
                        );
                        $item[$fieldName] = $buyerPhone ?? '';
                    }
                }
            }
        }
        return $dataSource;
    }
}
