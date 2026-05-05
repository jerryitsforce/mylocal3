<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       09/02/2026
 */

namespace Branch8\MarketplaceProduct\Model\PostSaveProcessor;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku;

class StockAndQuantity implements PostSaveProcessorInterface
{
    /**
     * @var GetSalableQuantityDataBySku
     */
    private $getSalableQuantityDataBySku;
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @param GetSalableQuantityDataBySku $getSalableQuantityDataBySku
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        GetSalableQuantityDataBySku $getSalableQuantityDataBySku,
        ResourceConnection $resourceConnection
    )
    {
        $this->getSalableQuantityDataBySku = $getSalableQuantityDataBySku;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param RequestInterface $request
     * @param int $productId
     * @param $wholeData
     * @return void
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException
     */
    public function process(RequestInterface $request, int $productId, &$wholeData = [])
    {
        $isDraftState = isset($wholeData['back']) && ($wholeData['back'] === 'draft' || $wholeData['back'] === 'draft-duplicate');
        if (!$isDraftState && isset($wholeData['product']['salable_qty'])) {
            $currentSalableQty = $this->getSalableQty($wholeData['product']['sku']);
            if ((int)$wholeData['product']['salable_qty'] != 0 || $currentSalableQty > 0) {
                $salableQty = (int)$wholeData['product']['salable_qty'] - $this->getReservedQuantityBySku($wholeData['product']['sku']);
                if ($salableQty < 0) {
                    $salableQty = 0;
                }
                $wholeData['product']['quantity_and_stock_status']['qty'] = $salableQty;
                if ($salableQty > 0) {
                    $wholeData['product']['quantity_and_stock_status']['is_in_stock'] = 1;
                } else {
                    $wholeData['product']['quantity_and_stock_status']['is_in_stock'] = 0;
                }
            } else {
                $wholeData['product']['quantity_and_stock_status']['is_in_stock'] = 0;
            }
        }
    }

    /**
     * @param $sku
     * @return int
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\InventoryConfigurationApi\Exception\SkuIsNotAssignedToStockException
     */
    private function getSalableQty($sku): int
    {
        if ($sku) {
            $salableQty = $this->getSalableQuantityDataBySku->execute($sku);
            if (isset($salableQty[0]['qty'])) {
                return (int)$salableQty[0]['qty'];
            }
        }
        return 0;
    }
    /**
     * @param string $sku
     * @return int
     */
    private function getReservedQuantityBySku(string $sku): int
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $connection->getTableName('inventory_reservation');
        $select = $connection->select()
            ->from(['ir' => $tableName], [])
            ->columns(['reserved_qty' => new \Zend_Db_Expr('SUM(ir.quantity)')]) // Sum the quantity
            ->where('ir.sku = ?', $sku)
            ->group('ir.sku');
        $reservedQty = $connection->fetchOne($select);
        return $reservedQty !== false ? (int)$reservedQty : 0;
    }
}
