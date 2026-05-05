<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Branch8\AdvancedPermissions\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\App\ResourceConnection;

class Customerlink extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    protected $coreRegistry;

    /**
     * Constructor.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param ResourceConnection $resourceConnection
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        ResourceConnection $resourceConnection,
        \Magento\Framework\Registry $coreRegistry,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->resourceConnection = $resourceConnection;
        $this->coreRegistry = $coreRegistry;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source.
     *
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            $connection = $this->resourceConnection->getConnection();
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['entity_id'])) {
                    if (!isset($item[$fieldName])) {
                        // Try marketplace_orders table
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

                        $orderId = (int)$item['entity_id'];
                        $sellerId = isset($orderSellerRelate[$orderId]) ? $orderSellerRelate[$orderId] : NULL;

                        if ($sellerId) {
                            $arraySeller = [];
                            $resultNickname = '';
                            $resultPhone = '';
                            
                            $customerGrid = $connection->getTableName('customer_grid_flat');
                            $sellerInfo = $connection->fetchAll(
                                $connection->select()
                                    ->from($customerGrid, ['name', 'nickname', 'phone_number'])
                                    ->where('entity_id = ?', $sellerId)
                                    ->limit(1)
                            );
                            if (!empty($sellerInfo)) {
                                $info = $sellerInfo[0];
                                $sellerName = $info['name'] ?? '';
                                $arraySeller[] = $sellerId . ';' . $sellerName;
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
                            
                            $item['nickname'] = $item['nickname'] ?? $resultNickname;
                            $item['phone_number'] = $item['phone_number'] ?? $resultPhone;
                            $item['seller'] = !empty($arraySeller) ? implode(',', $arraySeller) : '';
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
                        $item[$fieldName] = implode(' ', $result);
                    }

                }
            }
        }

        return $dataSource;
    }
}
