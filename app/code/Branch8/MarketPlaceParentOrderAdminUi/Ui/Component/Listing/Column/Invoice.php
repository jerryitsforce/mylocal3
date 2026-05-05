<?php

declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Sales\Ui\Component\Listing\Column\Invoice\State\Options;
use Magento\Ui\Component\Listing\Columns\Column;

class Invoice extends Column
{
    /** @var Options */
    protected Options $option;

    /** @var UrlInterface */
    protected UrlInterface $urlBuilder;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param Options $option
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Options $option,
        UrlInterface $urlBuilder,
        array $components,
        array $data
    ) {
        $this->option = $option;
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            $options = $this->option->toOptionArray();
            $options = array_combine(array_column($options, 'value'), array_column($options, 'label'));
            foreach ($dataSource['data']['items'] as &$item) {
                if (!isset($item[$fieldName])) {
                    try {
                        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                        $resource = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
                        $connection = $resource->getConnection();
                        $spocTable = $connection->getTableName('sales_parent_order_children');
                        $orderIds = $connection->fetchCol(
                            $connection->select()
                                ->from($spocTable, ['children_id'])
                                ->where('parent_id = ?', (int)$item['entity_id'])
                        );

                        if ($orderIds) {
                            $table = $resource->getTableName('sales_invoice_grid');
                            $invoiceInfo = $connection->fetchAll(
                                $connection->select()
                                    ->from($table, [
                                        'invoice_status' => 'GROUP_CONCAT(DISTINCT CONCAT(entity_id,";",state) ORDER BY entity_id SEPARATOR "|")',
                                        'invoice_modification_date' => 'GROUP_CONCAT(DISTINCT updated_at SEPARATOR "|")'
                                    ])
                                    ->where('order_id IN (?)', $orderIds)
                            );
                            if (!empty($invoiceInfo)) {
                                $item['invoice_status'] = $invoiceInfo[0]['invoice_status'] ?? '';
                                $item['invoice_modification_date'] = $invoiceInfo[0]['invoice_modification_date'] ?? '';
                            }
                        }
                    } catch (\Exception $e) {
                    }
                }
                $values = [];
                foreach ((!empty($item[$fieldName]) ? explode('|', $item[$fieldName]) : []) as $value) {
                    list($id, $name) = explode(';', $value);
                    $values[] = "<a href='" . $this->urlBuilder->getUrl(
                            'sales/invoice/view', ['invoice_id' => $id]
                        ) . "' target='_blank'>" . ($options[$name] ?? $name) . '</a>';
                }
                $item[$fieldName] = join('<br>', $values);
            }
        }
        return $dataSource;
    }
}
