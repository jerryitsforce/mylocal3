<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       03/02/2026
 */

namespace Branch8\OptionsWithStockAndImages\Model;

use Magento\Catalog\Model\Product;
use Psr\Log\LoggerInterface;
use Branch8\OptionsWithStockAndImages\Helper\Salable;

class VariantChangeHandler
{
    /**
     * @var \Branch8\OptionsWithStockAndImages\Helper\Salable
     */
    protected $salable;

    private $processed = [];

    private LoggerInterface $logger;

    /**
     * @param \Branch8\OptionsWithStockAndImages\Helper\Salable $salable
     * @param LoggerInterface $logger
     */
    public function __construct(
        Salable         $salable,
        LoggerInterface $logger
    )
    {
        $this->logger = $logger;
        $this->salable = $salable;
    }

    /**
     * @param Product $product
     * @return $this
     */
    public function execute(Product $product)
    {
        if (isset($this->processed[$product->getId()])) {
            return $this;
        }
        try {
            $this->logger->info('Branch8\OptionsWithStockAndImages\Model\VariantChangeHandler::execute(' . $product->getId() . ')');
            $this->salable->changeManageStock($product);
            $this->salable->changeCostForVariation($product);
            $this->processed[$product->getId()] = true;
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());
            $this->logger->debug($exception->getTraceAsString());
        }
        return $this;
    }
}
