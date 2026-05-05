<?php
declare(strict_types=1);

namespace Branch8\PointMoneyConfig\Model\Services;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Branch8\PointMoneyConfig\Helper\RecommendProduct;
use Branch8\PointMoneyConfig\Helper\Common as Helper;
use Exception;
use Psr\Log\LoggerInterface;
use Magento\Eav\Model\Config as EavConfig;

class SyncPointMoney
{
    /**
     * @var ResourceConnection
     */
    private $resouceConnection;

    /**
     * @var RecommendProduct
     */
    private $recommendProduct;

    /** @var Helper */
    private $helper;

    /** @var Magento\Catalog\Model\ResourceModel\Product\CollectionFactory */
    private $_productCollectionFactory;

    /**
     * @var EavConfig
     */
    private EavConfig $eavConfig;

    /**
     * @param ResourceConnection $resourceConnection
     * @param Config $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        EavConfig $eavConfig,
        Helper $helper,
        RecommendProduct $recommendProduct,
        ResourceConnection $resourceConnection,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
    )
    {
        $this->eavConfig = $eavConfig;
        $this->helper = $helper;
        $this->recommendProduct = $recommendProduct;
        $this->resouceConnection = $resourceConnection;
        $this->_productCollectionFactory = $productCollectionFactory;
    }

    /**
     * @param $ids
     * @return bool
     */
    public function syncIds($ids)
    {
        return $this->sync($ids);
    }

    /**
     * @return bool
     */
    public function syncAll()
    {
        return $this->sync();
    }

    /**
     * @return bool
     */
    private function sync($ids = [])
    {
        //$ids = [3051];
        $rowIds = [];
        $collection = $this->_productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        if ($ids) {
            $collection->addFieldToFilter('entity_id', ['in' => $ids]);
            $rowIds = $collection->getColumnValues('row_id');
        }

        $this->removeAllValue($rowIds);

        foreach($collection as $product){
            $point_money_config_product_point = $this->recommendProduct->getPointMoneyConfigProductPoint(
                $this->getProductPrice($product),
                $product->getData($this->helper::ATTRIBUTE_CODE_TYPE),
                $product->getData($this->helper::ATTRIBUTE_CODE_FREE_RATIO_LOWER_REDEEM_LIMIT_TYPE),
                $product->getData($this->helper::ATTRIBUTE_CODE_FREE_RATIO_LOWER_REDEEM_LIMIT_VALUE)
            );
            if($point_money_config_product_point === null){
                // save product to set data to null
                try{
                    //$product->setData($this->helper::ATTRIBUTE_CODE_PRODUCT_POINT, $point_money_config_product_point);
                    //$product->save();
                }
                catch(Exception $e){
                    continue;
                }
            } else {
                $product->setData($this->helper::ATTRIBUTE_CODE_PRODUCT_POINT, $point_money_config_product_point);
                $product->getResource()->saveAttribute($product, $this->helper::ATTRIBUTE_CODE_PRODUCT_POINT);
            }
        }
        return true;
    }

    protected function getProductPrice(Product $product)
    {
        if ($product->getId() && $product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE) {
            return $product->getFinalPrice();
        }

        if($product->getFinalPrice()){
            return $product->getFinalPrice();
        }

        return $product->getPrice();
    }

    protected function removeAllValue($rowIds){
        $connection = $this->resouceConnection->getConnection();
        $attribute = $this->eavConfig->getAttribute(\Magento\Catalog\Model\Product::ENTITY, $this->helper::ATTRIBUTE_CODE_PRODUCT_POINT);
        $attributeId = $attribute->getId();
        if ($attributeId > 0) {
            $table = $attribute->getBackendTable();
            $sql = "DELETE FROM {$table} WHERE `attribute_id` = {$attributeId} AND `store_id` >= 0";
            if(count($rowIds)){
                $rowIds = implode(',', $rowIds);
                $sql = "DELETE FROM {$table} WHERE `attribute_id` = {$attributeId} AND `store_id` >= 0 AND `row_id` IN ({$rowIds})";
            }
            $connection->query($sql);
        }
    }
}
