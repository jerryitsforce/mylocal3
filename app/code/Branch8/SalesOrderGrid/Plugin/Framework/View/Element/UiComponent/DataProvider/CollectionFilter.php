<?php
declare(strict_types=1);

namespace Branch8\SalesOrderGrid\Plugin\Framework\View\Element\UiComponent\DataProvider;

use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection;
use Magento\Framework\Data\Collection\AbstractDb;

class CollectionFilter
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Add manual invoice fields to sales order grid collection before loading
     *
     * @param Collection $subject
     * @return void
     */
    public function beforeLoad(Collection $subject)
    {
        $this->addManualInvoiceFields($subject);
    }

    /**
     * Add manual invoice number and is_manual_invoice fields
     *
     * @param AbstractDb $collection
     * @return void
     */
    private function addManualInvoiceFields(AbstractDb $collection): void
    {
        $select = $collection->getSelect();
        $fromPart = $select->getPart(\Magento\Framework\DB\Select::FROM);
        
        // Check if manual_invoice join already exists
        if (!isset($fromPart['manual_invoice'])) {
            $manualInvoiceTable = $this->resourceConnection->getTableName('manual_invoice');
            
            $select->joinLeft(
                ['manual_invoice' => $manualInvoiceTable],
                'main_table.entity_id = manual_invoice.order_id',
                [
                    'manual_invoice_number' => 'manual_invoice.invoice_number',
                    'is_manual_invoice' => new \Zend_Db_Expr(
                        'CASE WHEN manual_invoice.invoice_number IS NOT NULL THEN 1 ELSE 0 END'
                    )
                ]
            );
        }
    }

    /**
     * Add filter conditions for manual invoice fields
     *
     * @param Collection $subject
     * @param callable $proceed
     * @param string $field
     * @param string|array $condition
     * @return Collection
     */
    public function aroundAddFieldToFilter(Collection $subject, callable $proceed, $field, $condition = null)
    {
        if ($field === 'manual_invoice_number') {
            $this->addManualInvoiceFields($subject);
            $subject->getSelect()->where(
                'manual_invoice.invoice_number LIKE ?',
                '%' . $condition['like'] . '%'
            );
            return $subject;
        }
        
        if ($field === 'is_manual_invoice') {
            $this->addManualInvoiceFields($subject);
            if ($condition['eq'] == '1') {
                $subject->getSelect()->where('manual_invoice.invoice_number IS NOT NULL');
            } else {
                $subject->getSelect()->where('manual_invoice.invoice_number IS NULL');
            }
            return $subject;
        }
        
        return $proceed($field, $condition);
    }
}