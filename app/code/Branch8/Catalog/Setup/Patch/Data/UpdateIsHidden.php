<?php

namespace Branch8\Catalog\Setup\Patch\Data;
use Magento\Framework\Setup\Patch\DataPatchInterface;
class UpdateIsHidden implements DataPatchInterface{
    /**
     * @var \Magento\Catalog\Model\Product\Action
     */
    protected $productAction;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Collection
     */
    protected $productCollection;

    /**
     * @param \Magento\Catalog\Model\Product\Action $productAction
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection
     */
    public function __construct(
        \Magento\Catalog\Model\Product\Action $productAction,
        \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection
    ){
        $this->productAction = $productAction;
        $this->productCollection = $productCollection;
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies()
    {
        // TODO: Implement getDependencies() method.
        return [];
    }

    /**
     * @return array|string[]
     */
    public function getAliases()
    {
        // TODO: Implement getAliases() method.
        return [];
    }

    /**
     * @return void
     */
    public function apply()
    {
        // TODO: Implement apply() method.
        $products = $this->productCollection->addAttributeToSelect('entity_id');
        $pids = [];
        foreach ($products as $_product){
            $pids[] =$_product->getId();
        }
        if (empty($pids)) {
            return; // No products to update
        }
        $this->productAction->updateAttributes($pids, ['is_hidden' => \Branch8\Catalog\Model\Source\HiddenTypeFull::NOT_HIDDEN], 0);
    }
}
