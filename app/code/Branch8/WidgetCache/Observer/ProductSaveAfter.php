<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\WidgetCache\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Branch8\WidgetCache\Helper\Data as WidgetCacheHelper;
use Branch8\WidgetCache\Helper\Log as ModuleLog;

/**
 * Class ProductSaveAfter
 * 
 * Observer to clear widget cache when product is saved or deleted
 */
class ProductSaveAfter implements ObserverInterface
{
    private const LOG_OPTION = 'ProductSaveAfter';
    /**
     * @var WidgetCacheHelper
     */
    private $widgetCacheHelper;

    /**
     * @var ModuleLog
     */
    private ModuleLog $moduleLog;

    /**
     * ProductSaveAfter constructor
     *
     * @param WidgetCacheHelper $widgetCacheHelper
     * @param ModuleLog $moduleLog Widget cache module logger helper.
     */
    public function __construct(
        WidgetCacheHelper $widgetCacheHelper,
        ModuleLog $moduleLog
    ) {
        $this->widgetCacheHelper = $widgetCacheHelper;
        $this->moduleLog = $moduleLog;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if (!$this->widgetCacheHelper->isWidgetCacheEnabled()) {
            return;
        }

        try {
            $product = $observer->getEvent()->getData('product');
            
            if (!$product || !$product->getId()) {
                return;
            }

            $productId = $product->getId();
            
            // Clear cache tags related to this specific product
            // Include both specific product tag and general catalog_product tag
            // to ensure all widgets containing this product are cleared
            $tags = [
                'catalog_product_' . $productId,  // Specific product tag
                'catalog_product',                 // General product tag (for widgets that may include this product)
                'catalog_product_list'             // Product list widget tag
            ];
            
            // Clear cache for widgets that contain this product
            $this->widgetCacheHelper->clearWidgetCache($tags);
            
            $this->widgetCacheHelper->logDebug(
                'Widget cache cleared for product',
                [
                    'product_id' => $productId,
                    'product_sku' => $product->getSku(),
                    'product_name' => $product->getName(),
                    'tags' => $tags
                ]
            );
            
        } catch (\Exception $e) {
            $this->moduleLog->exception(
                $e,
                self::LOG_OPTION,
                __METHOD__
            );
        }
    }
}

