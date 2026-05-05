<?php

declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Ui\Component\Listing\Column;

use Magento\Framework\Stdlib\BooleanUtils;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Date extends Column
{
    /** @var TimezoneInterface */
    protected TimezoneInterface $timezone;

    /** @var BooleanUtils */
    protected BooleanUtils $booleanUtils;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param TimezoneInterface $timezone
     * @param BooleanUtils $booleanUtils
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        TimezoneInterface $timezone,
        BooleanUtils $booleanUtils,
        array $components,
        array $data
    ) {
        $this->timezone = $timezone;
        $this->booleanUtils = $booleanUtils;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as & $item) {
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
                    if ($value !== '0000-00-00 00:00:00') {
                        $timezone = isset($this->getConfiguration()['timezone'])
                            ? $this->booleanUtils->convert($this->getConfiguration()['timezone'])
                            : true;
                        $date = $timezone ? $this->timezone->date(new \DateTime($value)) : new \DateTime($item[$this->getData('name')]);
                        $values[] = $date->format('Y-m-d H:i:s');
                    }
                }
                $item[$fieldName] = join('<br>', $values);
            }
        }
        return $dataSource;
    }
}
