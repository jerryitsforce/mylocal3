<?php
declare(strict_types=1);

namespace Branch8\OptionsWithStockAndImages\Observer;

use Branch8\OptionsWithStockAndImages\Model\VariantChangeHandler;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Dispatcher for the `catalog_product_save_commit_after` event.
 */
class CatalogProductSaveCommitAfterObserver implements ObserverInterface
{
    private VariantChangeHandler $variantChangeHandler;

    /**
     * @param VariantChangeHandler $variantChangeHandler
     */
    public function __construct(
        VariantChangeHandler $variantChangeHandler,
    )
    {
        $this->variantChangeHandler = $variantChangeHandler;
    }

    /**
     * Handle the `catalog_product_save_commit_after` event.
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $product = $observer->getEvent()->getProduct();
        if ($product) {
            $this->variantChangeHandler->execute($product);
        }
    }
}
