<?php

declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Seller extends Column
{
    /** @var UrlInterface */
    protected UrlInterface $urlBuilder;

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
     * @inheritDoc
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['entity_id'])) {
                    if (!isset($item[$fieldName])) {
                        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                        $resource = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
                        $connection = $resource->getConnection();
                        $pOrderId = (int)$item['entity_id'];
                        $spocTable = $connection->getTableName('sales_parent_order_children');
                        $orderIds = $connection->fetchCol(
                            $connection->select()
                                ->from($spocTable, ['children_id'])
                                ->where('parent_id = ?', $pOrderId)
                        );

                        if ($orderIds) {
                            // Try marketplace_orders table
                            $mpTable = $connection->getTableName('marketplace_orders');
                            $sellerId = $connection->fetchCol(
                                $connection->select()
                                    ->from($mpTable, ['seller_id'])
                                    ->where('order_id IN (?)', $orderIds)
                            );

                            if ($sellerId) {
                                $arraySeller = [];
                                $resultNickname = '';
                                $resultPhone = '';
                                foreach ($sellerId as $id) {
                                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                                    $resource = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
                                    $connection = $resource->getConnection();
                                    $customerGrid = $connection->getTableName('customer_grid_flat');

                                    $sellerInfo = $connection->fetchAll(
                                        $connection->select()
                                            ->from($customerGrid, ['name', 'nickname', 'phone_number'])
                                            ->where('entity_id = ?', $id)
                                            ->limit(1)
                                    );
                                    if (!empty($sellerInfo)) {
                                        $info = $sellerInfo[0];
                                        $sellerName = $info['name'] ?? '';
                                        $arraySeller[] = $id . ';' . $sellerName;
                                        if (!$resultNickname) {
                                            $resultNickname = $info['nickname'] ?? '';
                                        } else {
                                            $resultNickname .= $info['nickname'] ? ', ' . $info['nickname'] : '';
                                        }
                                        if (!$resultPhone) {
                                            $resultPhone = $info['phone_number'] ?? '';
                                        } else {
                                            $resultPhone .= $info['phone_number'] ? ', ' . $info['phone_number'] : '';
                                        }
                                    }
                                }
                                $item['nickname'] = $item['nickname'] ?? $resultNickname;
                                $item['phone_number'] = $item['phone_number'] ?? $resultPhone;
                                $item['seller'] = !empty($arraySeller) ? implode(',', $arraySeller) : '';
                            }
                        }
                    }
                }

                if ($fieldName == 'seller') {
                    $customerIds = !empty($item[$fieldName]) ? explode(',', $item[$fieldName]) : [];
                    // Fallback: load seller IDs if no customer_ids are present
                    $result = [];
                    foreach ($customerIds as $customerIdNames) {
                        $customerIdName = explode(';', $customerIdNames);
                        $sellerId = (int)$customerIdName[0];
                        $sellerName = $customerIdName[1] ?? null;
                        $result[] = "<a href='" . $this->urlBuilder->getUrl(
                                'customer/index/edit',
                                ['id' => $sellerId, 'seller_panel' => 1]
                            ) . "' target='_blank' title='" .
                            __('View Seller') . "'>" . $sellerName . '</a>';
                    }
                    $item[$fieldName] = join('<br>', $result);
                }
            }
        }
        return $dataSource;
    }
}
