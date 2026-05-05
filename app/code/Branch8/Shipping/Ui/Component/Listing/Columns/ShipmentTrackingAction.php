<?php

declare(strict_types=1);

namespace Branch8\Shipping\Ui\Component\Listing\Columns;

use Branch8\Sales\Helper\Config as SalesConfig;
use Branch8\Sales\ViewModel\StatusResolver;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class ShipmentTrackingAction extends Column
{
    /**
     * @var SalesConfig
     */
    private SalesConfig $salesConfig;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var AuthorizationInterface
     */
    private AuthorizationInterface $authorization;

    /**
     * @var AdapterInterface
     */
    private $_conn;

    /**
     * Constructor.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param SalesConfig $salesConfig
     * @param SerializerInterface $serializer
     * @param AuthorizationInterface $authorization
     * @param ResourceConnection $resourceConnection
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface       $context,
        UiComponentFactory     $uiComponentFactory,
        SalesConfig            $salesConfig,
        SerializerInterface    $serializer,
        AuthorizationInterface $authorization,
        ResourceConnection     $resourceConnection,
        array                  $components = [],
        array                  $data = []
    ) {
        parent::__construct(
            $context,
            $uiComponentFactory,
            $components,
            $data
        );
        $this->salesConfig = $salesConfig;
        $this->serializer = $serializer;
        $this->authorization = $authorization;
        $this->_conn = $resourceConnection->getConnection();
    }

    /**
     * @inheritdoc
     */
    public function prepare(): void
    {
        if ($this->context->getNamespace() === 'sales_order_grid') {
            if (!$this->authorization->isAllowed('Branch8_Shipping::shipping')) {
                $this->_data['config']['componentDisabled'] = true;
            }
        }

        parent::prepare();
    }

    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as &$item) {
                $orderId = (int)($item['order_id'] ?? $item['entity_id'] ?? false);
                $orderData = [
                    'is_virtual' => $item['is_virtual'],
                    'status' => $item['status']
                ];
                
                if ($this->isDisplayAction($orderData)) {
                    $item[$fieldName] = '<button class="add-tracking-button" data-id="' . $orderId . '">' . __('Manage') . '</button>';
                    $item[$fieldName . '_carriers'] = $this->serializer->serialize($this->salesConfig->getLogisticsCompanies());
                    $item[$fieldName . '_getlistaction'] = $this->context->getUrl('shipping/order_shipment_tracking/getlist');
                    $item[$fieldName . '_addaction'] = $this->context->getUrl('shipping/order_shipment_tracking/add');
                    $item[$fieldName . '_removeaction'] = $this->context->getUrl('shipping/order_shipment_tracking/remove');
                }
            }
        }
        return $dataSource;
    }

    /**
     * @param array $order
     * @return bool
     */
    private function isDisplayAction(array $order): bool
    {
        if ($order['is_virtual']) {
            return false;
        }
        $status = $order['status'];
        if (in_array($status, StatusResolver::shippingStatues())) {
            return true;
        }

        return false;
    }
}
