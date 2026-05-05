<?php
declare(strict_types=1);

namespace Branch8\OptionsWithStockAndImages\Console\Command;

use Exception;
use Magento\Framework\App\ResourceConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Branch8\OptionsWithStockAndImages\Logger\Logger as CustomLogger;

class FixReadyToShipQty extends Command
{
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var CustomLogger
     */
    private $customLogger;

    /**
     * @var array
     */
    private $orderCache = [];

    /**
     * @var array
     */
    private $shipmentLogCache = [];

    /**
     * @var array
     */
    private $processedHistory = [];

    /**
     * @param ResourceConnection $resourceConnection
     * @param CustomLogger $customLogger
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        CustomLogger $customLogger
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->customLogger = $customLogger;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->setName("sales:fix-ready-to-ship-qty");
        $this->setDescription("Fix ready_to_ship_qty by subtracting quantities from completed orders found in stock movement logs.");
        parent::configure();
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $output->writeln("<info>Starting to fix ready_to_ship_qty (Optimized)...</info>");
            $this->customLogger->info('--- Starting to fix ready_to_ship_qty (Optimized) ---');

            $variations = $this->getVariationsWithReadyToShipQty();
            if (empty($variations)) {
                $output->writeln("<comment>No variations found with ready_to_ship_qty > 0.</comment>");
                return Command::SUCCESS;
            }

            $output->writeln(sprintf("<info>Found %d variations. Pre-loading data...</info>", count($variations)));
            $this->customLogger->info(sprintf("Found %d variations.", count($variations)));

            $this->preloadData($variations);

            foreach ($variations as $variation) {
                $this->processVariation($variation, $output);
            }

            $output->writeln("<info>Finished fixing ready_to_ship_qty. Log management is available in system config.</info>");
            $this->customLogger->info('--- Finished fixing ready_to_ship_qty ---');
            return Command::SUCCESS;
        } catch (Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            if ($this->customLogger) {
                $this->customLogger->error($e->getMessage());
            }
            return Command::FAILURE;
        }
    }

    /**
     * @return array
     */
    private function getVariationsWithReadyToShipQty(): array
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('wk_osi_variations');

        $select = $connection->select()
            ->from($tableName)
            ->where('ready_to_ship_qty > 0');

        return $connection->fetchAll($select);
    }

    /**
     * Pre-load processing history and shipment logs to minimize DB queries
     * @param array $variations
     * @return void
     */
    private function preloadData(array $variations): void
    {
        $connection = $this->resourceConnection->getConnection();
        $productIds = array_unique(array_column($variations, 'mageproduct_id'));
        
        // 1. Pre-load Processed History
        $historyTable = $this->resourceConnection->getTableName('wk_osi_variations_fix_ready_to_ship_qty');
        $historySelect = $connection->select()
            ->from($historyTable, ['order_id', 'product_id', 'variation_comb']);
        
        $historyData = $connection->fetchAll($historySelect);
        foreach ($historyData as $row) {
            $key = $row['order_id'] . '_' . $row['product_id'] . '_' . $row['variation_comb'];
            $this->processedHistory[$key] = true;
        }

        // 2. Pre-load Shipment Logs
        $movementTable = $this->resourceConnection->getTableName('kiwicommerce_stock_movement');
        $shipmentSelect = $connection->select()
            ->from($movementTable, ['product_id', 'message'])
            ->where('product_id IN (?)', $productIds)
            ->where('message LIKE ?', 'Product deducted by Shipment (order: %')
            ->where('created_at >= ?', '2026-04-01 00:00:00');
        
        $shipmentLogs = $connection->fetchAll($shipmentSelect);
        foreach ($shipmentLogs as $log) {
            $msg = $log['message'];
            if (preg_match('/order:\s*([^\s)]+)/', $msg, $matches)) {
                $incId = trim($matches[1]);
                $this->shipmentLogCache[$log['product_id'] . '_' . $incId] = true;
            }
        }
        
        $this->customLogger->info(sprintf("Pre-loaded %d history records and %d shipment logs.", count($this->processedHistory), count($this->shipmentLogCache)));
    }

    /**
     * @param array $variation
     * @param OutputInterface $output
     * @return void
     */
    private function processVariation(array $variation, OutputInterface $output): void
    {
        $connection = $this->resourceConnection->getConnection();
        $variationId = $variation['entity_id'];
        $mageProductId = (int)$variation['mageproduct_id'];
        $variationSku = $variation['sku'];
        $variationComb = $variation['comb'];
        $readyToShipQty = (int)$variation['ready_to_ship_qty'];

        // Find movement logs for this product
        $movementTable = $this->resourceConnection->getTableName('kiwicommerce_stock_movement');
        $select = $connection->select()
            ->from($movementTable)
            ->where('product_id = ?', $mageProductId)
            ->where('message LIKE ?', 'Product ordered%');

        $movements = $connection->fetchAll($select);
        
        if (empty($movements)) return;

        $totalToSubtract = 0;
        foreach ($movements as $movement) {
            if (preg_match('/order:\s*([^\s)]+)/', $movement['message'], $matches)) {
                $incrementId = trim($matches[1]);
                
                // Check if already processed for this variation/order/product
                if ($this->cachedHasShipmentLog($mageProductId, $incrementId)) {
                    continue;
                }

                $orderData = $this->getOrderByIncrementId($incrementId);
                if ($orderData && in_array($orderData['status'], ['complete', 'canceled', 'closed'])) {
                    $orderId = (int)$orderData['entity_id'];

                    if ($this->isAlreadyProcessed($orderId, $mageProductId, $variationComb)) {
                        continue;
                    }

                    $itemsSelect = $connection->select()
                        ->from($this->resourceConnection->getTableName('sales_order_item'), ['item_id', 'qty_ordered', 'variation_sku', 'spec_title', 'product_options'])
                        ->where('order_id = ?', $orderId)
                        ->where('product_id = ?', $mageProductId);
                    
                    $items = $connection->fetchAll($itemsSelect);
                    foreach ($items as $item) {
                        if ($this->isItemMatchingVariation($item, $variationSku, $variationComb)) {
                            $qty = (float)$item['qty_ordered'];
                            $totalToSubtract += $qty;
                            $this->saveToTrackingTable($variationComb, $mageProductId, $orderId, (int)$item['item_id'], $qty);
                            
                            $logMsg = sprintf("Matched Variation %d with Order %s (Qty %f)", $variationId, $incrementId, $qty);
                            $this->customLogger->info($logMsg);
                        }
                    }
                }
            }
        }

        if ($totalToSubtract > 0) {
            $newQty = max(0, $readyToShipQty - (int)$totalToSubtract);
            $connection->update(
                $this->resourceConnection->getTableName('wk_osi_variations'), 
                ['ready_to_ship_qty' => $newQty], 
                ['entity_id = ?' => $variationId]
            );
            $this->customLogger->info(sprintf("Updated Variation %d (Prod ID: %d, Comb: %s): %d -> %d", $variationId, $mageProductId, $variationComb, $readyToShipQty, $newQty));
        }
    }

    private function getOrderByIncrementId(string $incrementId): ?array
    {
        if (isset($this->orderCache[$incrementId])) return $this->orderCache[$incrementId];

        $connection = $this->resourceConnection->getConnection();
        $orderData = $connection->fetchRow(
            $connection->select()
                ->from($this->resourceConnection->getTableName('sales_order'), ['entity_id', 'status'])
                ->where('increment_id = ?', $incrementId)
        );
        $this->orderCache[$incrementId] = $orderData ?: null;
        return $this->orderCache[$incrementId];
    }

    private function cachedHasShipmentLog(int $productId, string $incrementId): bool
    {
        return isset($this->shipmentLogCache[$productId . '_' . $incrementId]);
    }

    private function isAlreadyProcessed(int $orderId, int $productId, string $comb): bool
    {
        $key = $orderId . '_' . $productId . '_' . $comb;
        return isset($this->processedHistory[$key]);
    }

    private function saveToTrackingTable(string $comb, int $productId, int $orderId, int $itemId, float $qty): void
    {
        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName('wk_osi_variations_fix_ready_to_ship_qty');
        $connection->insert($tableName, [
            'variation_comb' => $comb,
            'product_id' => $productId,
            'order_id' => $orderId,
            'item_id' => $itemId,
            'qty' => $qty
        ]);
        $key = $orderId . '_' . $productId . '_' . $comb;
        $this->processedHistory[$key] = true;
    }

    private function isItemMatchingVariation(array $item, string $vSku, string $vComb): bool
    {
        if (!empty($item['variation_sku']) && $item['variation_sku'] === $vSku) return true;
        if (!empty($item['spec_title']) && $item['spec_title'] === $vComb) return true;

        if (!empty($item['product_options'])) {
            $options = json_decode($item['product_options'], true);
            if (isset($options['options'])) {
                $vals = array_column($options['options'], 'value');
                if (implode('-', $vals) === $vComb || (count($vals) === 1 && $vals[0] === $vComb)) return true;
            }
        }
        return false;
    }
}
