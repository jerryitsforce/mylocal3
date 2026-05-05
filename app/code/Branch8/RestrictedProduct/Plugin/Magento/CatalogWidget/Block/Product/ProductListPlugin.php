<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       14/04/2026
 */

namespace Branch8\RestrictedProduct\Plugin\Magento\CatalogWidget\Block\Product;

use Branch8\RestrictedProduct\Model\ConfigData;

class ProductListPlugin
{
    /**
     * @param ConfigData $configData
     */
    public function __construct(
        readonly ConfigData $configData,
    )
    {
    }

    /**
     * @param $subject
     * @param \Magento\Catalog\Model\ResourceModel\Product $collection
     * @return \Magento\Catalog\Model\ResourceModel\Product
     */
    public function afterGetBaseCollection($subject, \Magento\Catalog\Model\ResourceModel\Product\Collection $collection)
    {
        if($this->configData->enableOptimize()){
            $collection->getSelect()->reset(\Magento\Framework\DB\Select::DISTINCT);
            $collection->getSelect()->group('entity_id');
        }
        return $collection;
    }
}
