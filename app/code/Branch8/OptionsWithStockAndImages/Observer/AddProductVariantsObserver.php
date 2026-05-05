<?php
declare(strict_types=1);

namespace Branch8\OptionsWithStockAndImages\Observer;

use Branch8\OptionsWithStockAndImages\Model\Actions\GetProductVariants;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Dispatcher for the `sales_quote_item_collection_products_after_load` event.
 */
class AddProductVariantsObserver implements ObserverInterface
{
    private GetProductVariants $getProductVariants;

    /**
     * @param GetProductVariants $getProductVariants
     */
    public function __construct(GetProductVariants $getProductVariants)
    {
        $this->getProductVariants = $getProductVariants;
    }

    /**
     * Handle the `sales_quote_item_collection_products_after_load` event.
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var Collection $productCollection */
        $productCollection = $observer->getData('collection');
        foreach ($productCollection as $product) {
            $this->getProductVariants->execute($product);
        }
    }
}
