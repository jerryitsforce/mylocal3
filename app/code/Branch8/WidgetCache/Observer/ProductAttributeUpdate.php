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
 * Class ProductAttributeUpdate
 * 
 * Observer to clear widget cache when product attributes are updated in bulk
 */
class ProductAttributeUpdate implements ObserverInterface
{
    private const LOG_OPTION = 'ProductAttributeUpdate';
    /**
     * @var WidgetCacheHelper
     */
    private $widgetCacheHelper;

    /**
     * @var ModuleLog
     */
    private ModuleLog $moduleLog;

    /**
     * ProductAttributeUpdate constructor
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
            // Clear all product-related widget cache when attributes are updated in bulk
            $tags = [
                'catalog_product',
                'catalog_category',
                'widget_cache',
                'catalog_product_list',
                'bestseller_widget',
                'product_point_widget',
                'brand_list_widget'
            ];
            
            $this->widgetCacheHelper->clearWidgetCache($tags);
            
            $this->widgetCacheHelper->logDebug(
                'Widget cache cleared after product attribute update',
                ['tags' => $tags]
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

