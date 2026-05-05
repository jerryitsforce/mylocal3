<?php
declare(strict_types=1);

namespace Branch8\SalesReports\Model\ResourceModel\Report\Rule;

use Zend_Db_Select;

class Createdat extends \Magento\SalesRule\Model\ResourceModel\Report\Rule\Createdat
{
    /**
     * Resource Report Rule constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('salesrule_coupon_aggregated', 'id');
    }

    /**
     * Aggregate Coupons data by order created at
     *
     * @param mixed|null $from
     * @param mixed|null $to
     * @return $this
     */
    public function aggregate($from = null, $to = null)
    {
        return $this->_aggregateByOrder('created_at', $from, $to);
    }

    /**
     * Aggregate coupons reports by orders
     *
     * @param string $aggregationField
     * @param mixed $from
     * @param mixed $to
     * @return $this
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @throws \Exception
     */
    protected function _aggregateByOrder($aggregationField, $from, $to)
    {
        $table = $this->getMainTable();
        $sourceTable = $this->getTable('sales_order');
        $connection = $this->getConnection();
        $salesAdapter = $this->_resources->getConnection('sales');
        $connection->beginTransaction();
        try {
            if ($from !== null || $to !== null) {
                $subSelect = $this->_getTableDateRangeSelect($sourceTable, 'created_at', 'updated_at', $from, $to);
            } else {
                $subSelect = null;
            }
            $this->_clearTableByDateRange($table, $from, $to, $subSelect, false, $salesAdapter);
            $periodExpr = $connection->getDatePartSql(
                $this->getStoreTZOffsetQuery($sourceTable, $aggregationField, $from, $to, null, $salesAdapter)
            );
            $columns = [
                'period' => $periodExpr,
                'store_id' => 'store_id',
                'order_status' => new \Zend_Db_Expr('GROUP_CONCAT(DISTINCT status)'),
                'coupon_code' => new \Zend_Db_Expr("COALESCE(NULLIF(coupon_code, ''),'')"),
                'coupon_uses' => new \Zend_Db_Expr("COUNT(*)"),
                'rule_name' => 'coupon_rule_name',
                'coupon_price_rule' => 'coupon_price_rule',
                'coupon_rule_id'=>'coupon_rule_id',
                'subtotal_amount' => $connection->getIfNullSql(
                    'SUM((sales_order.base_subtotal - ' . $connection->getIfNullSql(
                        'sales_order.base_subtotal_canceled',
                        0
                    ) . ') * base_to_global_rate)',
                    0
                ),
                'total_amount' => $connection->getIfNullSql(
                    'SUM(((sales_order.base_subtotal - ' . $connection->getIfNullSql(
                        'sales_order.base_subtotal_canceled',
                        0
                    ) . ' + ' . $connection->getIfNullSql(
                        'sales_order.base_shipping_amount - ' . $connection->getIfNullSql('base_shipping_canceled', 0),
                        0
                    ) . ') - ' . $connection->getIfNullSql(
                        'ABS(sales_order.base_discount_amount) - ABS('
                        . $connection->getIfNullSql('sales_order.base_discount_canceled', 0) . ')',
                        0
                    ) . ' + ' . $connection->getIfNullSql(
                        'sales_order.base_tax_amount - ' . $connection->getIfNullSql('base_tax_canceled', 0),
                        0
                    ) . ')
                        * base_to_global_rate)',
                    0
                ),
                'subtotal_amount_actual' => $connection->getIfNullSql(
                    'SUM((sales_order.base_subtotal_invoiced - ' . $connection->getIfNullSql(
                        'sales_order.base_subtotal_refunded',
                        0
                    ) . ') * base_to_global_rate)',
                    0
                ),
                'discount_amount_actual' => $connection->getIfNullSql(
                    'SUM((sales_order.base_discount_invoiced - ' . $connection->getIfNullSql(
                        'sales_order.base_discount_refunded',
                        0
                    ) . ')
                        * base_to_global_rate)',
                    0
                ),
                'total_amount_actual' => $connection->getIfNullSql(
                    'SUM(((sales_order.base_subtotal_invoiced - ' . $connection->getIfNullSql(
                        'sales_order.base_subtotal_refunded',
                        0
                    ) . ' + ' . $connection->getIfNullSql(
                        'base_shipping_invoiced - ' . $connection->getIfNullSql('base_shipping_refunded', 0),
                        0
                    ) . ') - ' . $connection->getIfNullSql(
                        'ABS(sales_order.base_discount_invoiced) - ABS('
                        . $connection->getIfNullSql('sales_order.base_discount_refunded', 0) . ')',
                        0
                    ) . ' + ' . $connection->getIfNullSql(
                        'sales_order.base_tax_invoiced - ' . $connection->getIfNullSql('sales_order.base_tax_refunded', 0),
                        0
                    ) . ') * base_to_global_rate)',
                    0
                ),
                'cancellation_orders' => new \Zend_Db_Expr("SUM(CASE
           WHEN status = 'canceled' THEN 1
           ELSE 0
       END)"),
                'complete_orders' => new \Zend_Db_Expr("SUM(CASE
           WHEN status NOT IN ('canceled') THEN 1
           ELSE 0
       END)"),
                'order_ids' => new \Zend_Db_Expr('GROUP_CONCAT(increment_id)'),
                'suggested_retail_price_discounted' => new \Zend_Db_Expr(
                    'SUM(sales_order.subtotal_incl_tax)'
                ),
                'product_selling_price_discounted' => new \Zend_Db_Expr(
                    'SUM(sales_order.subtotal_incl_tax - ABS(sales_order.base_discount_amount))'
                ),
                'discount_amount' => $connection->getIfNullSql(
                    'SUM((ABS(sales_order.base_discount_amount)  * base_to_global_rate))',
                    0
                ),
                'vendor_share' => new \Zend_Db_Expr('
                                ROUND(SUM(CASE WHEN  (base_discount_amount IS NOT NULL AND  ABS(base_discount_amount) > 0)
                                THEN ABS(seller_borne_total_amount)
                                ELSE 0
                                END
                                ),0)'
                ),
                'platform_share' => new \Zend_Db_Expr(
                    'ROUND(
                                SUM(
                                    CASE
                                        WHEN (seller_borne_total_amount >0 AND base_discount_amount IS NOT NULL AND  ABS(base_discount_amount) > 0)
                                            THEN ABS(ABS(base_discount_amount) - ABS(seller_borne_total_amount))
                                        WHEN (platform_borne_total_amount > 0)
                                            THEN ABS(platform_borne_total_amount)
                                    ELSE 0
                                    END
                                ),0)'
                )
            ];
            $overallColumns=[
                'period' => $periodExpr,
                'overall_orders' => new \Zend_Db_Expr("COUNT(*)"),
                'overall_suggested_retail_price' => new \Zend_Db_Expr(
                    '
                    SUM(sales_order.subtotal_incl_tax)'
                ),
                'overall_product_selling_price' => new \Zend_Db_Expr(
                    'SUM(sales_order.subtotal_incl_tax - ABS(sales_order.base_discount_amount))'
                ),
            ];
            $select = $connection->select();
            $couponQuery = clone $select;
            $overallQuery = clone $select;
            $couponQuery->reset(\Magento\Framework\DB\Select::COLUMNS);
            $couponQuery->from(
                ['sales_order'],
                $columns
            )->where('coupon_code IS NOT NULL')->group(['period', 'coupon_code']);
            $overallQuery->from('sales_order', $overallColumns)->group('period');
            $select->from(
                ['d' => $couponQuery],
            )->join(
                ['t' => $overallQuery], 'd.period = t.period', ['overall_orders','overall_suggested_retail_price','overall_product_selling_price']
            )->order('d.period DESC, d.coupon_code');
            if ($subSelect !== null) {
                $select->having($this->_makeConditionFromDateRangeSelect($subSelect, 'period', $salesAdapter));
            }
            $aggregatedData = $salesAdapter->fetchAll($select);
            if ($aggregatedData) {
                $connection->insertOnDuplicate($table, $aggregatedData, array_keys($columns));
            }
            $select->reset();
            $columns = [
                'period' => 'period',
                'store_id' => new \Zend_Db_Expr('0'),
                'order_status' => 'order_status',
                'coupon_code' => 'coupon_code',
                'coupon_uses' => 'coupon_uses',
                'rule_name' => 'rule_name',
                'coupon_price_rule' => 'coupon_price_rule',
                'coupon_rule_id' => 'coupon_rule_id',
                'subtotal_amount' => 'subtotal_amount',
                'total_amount' => 'total_amount',
                'subtotal_amount_actual' => 'subtotal_amount_actual',
                'discount_amount_actual' => 'discount_amount_actual',
                'total_amount_actual' => 'total_amount_actual',
                'cancellation_orders' => 'cancellation_orders',
                'order_ids' => 'order_ids',
                'complete_orders' => 'complete_orders',
                'overall_orders'=> 'overall_orders',
                'overall_suggested_retail_price' => 'overall_suggested_retail_price',
                'overall_product_selling_price' => 'overall_product_selling_price',
                'suggested_retail_price_discounted' => 'suggested_retail_price_discounted',
                'product_selling_price_discounted' => 'product_selling_price_discounted',
                'discount_amount' => 'discount_amount',
                'vendor_share' => 'vendor_share',
                'platform_share' => 'platform_share'
            ];
            $select->from($table, $columns)->where('store_id <> 0');
            if ($subSelect !== null) {
                $select->where($this->_makeConditionFromDateRangeSelect($subSelect, 'period', $salesAdapter));
            }
            //$select->group(['period', 'order_status', 'coupon_code']);
            // $select->group(['period', 'order_status', 'coupon_code']);
            $connection->query($select->insertFromSelect($table, array_keys($columns)));
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }

        return $this;
    }
}
