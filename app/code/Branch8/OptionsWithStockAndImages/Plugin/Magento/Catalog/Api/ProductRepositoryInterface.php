<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\OptionsWithStockAndImages\Plugin\Magento\Catalog\Api;

use Magento\Catalog\Api\Data\ProductInterface;
use Branch8\PointMoneyConfig\Model\Services\SyncPointMoney;
use Magento\Framework\Exception\CouldNotSaveException;

class ProductRepositoryInterface
{

    /**
     * @var \Webkul\OptionsWithStockAndImages\Model\VariationsFactory
     */
    public $variationFactory;

    /**
     * @var \Webkul\OptionsWithStockAndImages\Model\SwatchFactory
     */
    public $swatchFactory;

    /**
     * @var \Branch8\OptionsWithStockAndImages\Helper\Salable
     */
    protected $salable;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $_stockRegistry;

    /**
     * @var \Branch8\SellerInformationProductDataExport\Model\Services\SyncSellerIdToIndexSellerId
     */
    protected $service;

    /**
     * @var SyncPointMoney
     */
    protected $syncPointMoney;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param CollectionFactory $variation
     */
    public function __construct(
        \Branch8\OptionsWithStockAndImages\Helper\Salable $salable,
        \Webkul\OptionsWithStockAndImages\Model\VariationsFactory $variationFactory,
        \Webkul\OptionsWithStockAndImages\Model\SwatchFactory $swatchFactory,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        SyncPointMoney $syncPointMoney,
        \Branch8\SellerInformationProductDataExport\Model\Services\SyncSellerIdToIndexSellerId $syncSellerIdToIndexSellerId
    ) {
        $this->variationFactory = $variationFactory;
        $this->swatchFactory = $swatchFactory;
        $this->salable = $salable;
        $this->_stockRegistry = $stockRegistry;
        $this->syncPointMoney = $syncPointMoney;
        $this->service = $syncSellerIdToIndexSellerId;
    }

    public function afterGet(
        \Magento\Catalog\Api\ProductRepositoryInterface $subject,
        $result
    ) {
        $variation = $this->getVariation($result->getRowId());
        $swatch = $this->getSwatch($result->getRowId());
        $extensionAttributes = $result->getExtensionAttributes(); /** get current extension attributes from entity **/
        $extensionAttributes->setVariation($variation);
        $extensionAttributes->setSwatch($swatch);
        $result->setExtensionAttributes($extensionAttributes);

        return $result;
    }

    public function afterGetList(
        \Magento\Catalog\Api\ProductRepositoryInterface $subject,
        $searchResults
    ) {
        $products = [];
        foreach ($searchResults->getItems() as $entity) {
            $variation = $this->getVariation($entity->getRowId());
            $swatch = $this->getSwatch($entity->getRowId());
            $extensionAttributes = $entity->getExtensionAttributes();
            $extensionAttributes->setVariation($variation);
            $extensionAttributes->setSwatch($swatch);
            $entity->setExtensionAttributes($extensionAttributes);

            $products[] = $entity;
        }
        $searchResults->setItems($products);
        return $searchResults;
    }

    /**
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $subject
     * @param ProductInterface $product
     * @param bool $saveOptions
     * @return array
     * @throws CouldNotSaveException
     */
    public function beforeSave(
        \Magento\Catalog\Api\ProductRepositoryInterface $subject,
        ProductInterface $product,
        $saveOptions = false
    ) : array
    {
        $productType = $product->getTypeId();
        if ($productType == 'simple') {
            if(!$product->getWeight() || $product->getWeight() <= 0){
                throw new CouldNotSaveException(__('Product Weight is required.'));
            }
        }
        $extensionAttributes = $product->getExtensionAttributes(); /** get original extension attributes from entity **/
        $variationData = $extensionAttributes->getVariation();
        if ($variationData && count($variationData)) {
            $options = $product->getOptions();
            $countMatrixOption = 0;
            $countVariation = 0;
            foreach ($options as $option) {
                if (empty($option)) {
                    continue;
                }
                $countOptionValue = 0;
                foreach ($option->getValues() as $value) {
                    if (empty($value)) {
                        continue;
                    }
                    $countOptionValue++;
                }
                if (!$countMatrixOption) $countMatrixOption = 1;
                $countMatrixOption = $countMatrixOption * $countOptionValue;
            }
            foreach ($variationData as $variation) {
                $variation = is_array($variation) ? $variation : $this->__toArray($variation);
                if (empty($variation['sku'])) {
                    throw new CouldNotSaveException(__('Product Variation SKU is required.'));
                }
                if (!$this->isNumericStrict($variation['stock'])) {
                    throw new CouldNotSaveException(__('Product Variation Stock is required.'));
                }
                if (empty($variation['cost'])) {
                    throw new CouldNotSaveException(__('Product Variation Cost is required.'));
                }
                if (empty($variation['price'])) {
                    throw new CouldNotSaveException(__('Product Variation Price is required.'));
                }
                $countVariation++;
            }
            if ($countVariation % $countMatrixOption != 0) {
                $result['error'] = 1;
                $result['msg'] = __('Please confirm that the following required Variation fields are filled in: cost, quantity, and product SKU.');
                return $result;
            }
        }
        return [$product, $saveOptions];
    }

    public function afterSave(
        \Magento\Catalog\Api\ProductRepositoryInterface $subject,
        ProductInterface $result, /** result from the save call **/
        ProductInterface $entity  /** original parameter to the call **/
        /** other parameter not required **/
    ) {
        $save = true;
        $productRowId = $result->getRowId();
        $productId=$result->getEntityId();
        if($result->getOptions()){
            $optionIdsArr = [];
            foreach ($result->getOptions() as $option) {
                $otpType = $option->getType();
                if ($otpType=="drop-down" || $otpType=="drop_down" || $otpType=="radio") {
                    $optionIdsArr[] = $option->getOptionId();
                } else {
                    $save = false;
                    break;
                }
            }
            if($save){
                $extensionAttributes = $entity->getExtensionAttributes(); /** get original extension attributes from entity **/
                $variationData = $extensionAttributes->getVariation();
                if ($variationData && count($variationData)) {
                    $this->deletePreviousData($productRowId);
                    $this->saveVariationArray($variationData, $productRowId, $productId);
                }

                $swatchData = $extensionAttributes->getSwatch();
                if ($swatchData && count($swatchData)) {
                    $this->deleteSwatchData($productRowId);
                    $this->saveSwatchArray($swatchData, $productRowId, $optionIdsArr);
                }
            } else {
                $this->deleteOldVariation($productRowId);
            }
        } else {
            $this->deleteOldVariation($productRowId);
        }
        // use catalog_product_save_commit_after
        /*$this->changeManageStock($result);
        $this->changeCostForVariation($result);*/

        $resultAttributes = $result->getExtensionAttributes(); /** get extension attributes as they exist after save **/
        $variation = $this->getVariation($result->getRowId());
        $swatch = $this->getSwatch($result->getRowId());
        $resultAttributes->setVariation($variation);
        $resultAttributes->setSwatch($swatch);
        $result->setExtensionAttributes($resultAttributes);

        $this->service->syncLivesearchInstockIds([$result->getId()]);
        $this->service->syncNeedToRefillIds([$result->getId()]);
        $this->syncPointMoney->syncIds([$result->getId()]);

        return $result;
    }

    protected function deleteOldVariation($productRowId){
        $this->deletePreviousData($productRowId);
        $this->deleteSwatchData($productRowId);
    }

    public function changeCostForVariation($product){
        $this->salable->changeCostForVariation($product);
    }

    public function changeManageStock($product){
        $this->salable->changeManageStock($product);
    }

    protected function getVariation($productId){
        $data = [];
        $variations = $this->variationFactory->create()
                                            ->getCollection()
                                            ->addFieldToFilter("product_id", $productId)
                                            ->getItems();
        foreach($variations as $variation){
            $data[] = $variation;
        }
        return $data;
    }

    protected function getSwatch($productId){
        $data = [];
        $swatchs = $this->swatchFactory->create()
                                            ->getCollection()
                                            ->addFieldToFilter("product_id", $productId)
                                            ->getItems();
        foreach($swatchs as $swatch){
            $data[] = $swatch;
        }
        return $data;
    }

    protected function saveVariationArray(array $wkvariation, $productId,$mageProductId){
        foreach ($wkvariation as $variation) {
            $variation = is_array($variation) ? $variation : $this->__toArray($variation);
            $variation['image'] = '';
            $variation['product_id'] = $productId;
            $variation['mageproduct_id'] = $mageProductId;
            unset($variation['entity_id']);
            $variationModel = $this->variationFactory->create()
                                    ->setData($variation);
            $this->_save($variationModel);
        }
    }

    protected function saveSwatchArray($wkswatch, $productId, $optionIdsArr){
        foreach ($wkswatch as $keyId => $swatch) {
            $swatch = is_array($swatch) ? $swatch : $this->__toArray($swatch);
            $swatch['product_id'] = $productId;
            if (isset($swatch['is_swatch'])
                &&
                ($swatch['is_swatch']=="on" || $swatch['is_swatch']==1 || $swatch['is_swatch']==true)
            ) {
                $swatch['is_swatch'] = 1;
            } else {
                $swatch['is_swatch'] = 0;
            }
            $swatch['option_id'] = $optionIdsArr[$keyId];
            unset($swatch['entity_id']);
            $swatchModel = $this->swatchFactory->create()->setData($swatch);
            $this->_save($swatchModel);
        }
    }

    private function __toArray($value): array
    {
        $data = [];
        $hasToArray = function ($model) {
            return is_object($model) && method_exists($model, '__toArray') && is_callable([$model, '__toArray']);
        };

        if(is_object($value) && method_exists($value, 'toArray')){
            return $value->toArray();
        }

        if ($hasToArray($value)) {
            $data = $value->__toArray();
        } elseif (is_array($value)) {
            foreach ($value as $nestedKey => $nestedValue) {
                if ($hasToArray($nestedValue)) {
                    $value[$nestedKey] = $nestedValue->__toArray();
                } else {
                    $data[$nestedKey] = $nestedValue;
                }
            }
        }

        return $data;
    }

    /**
     * Save object
     *
     * @param VariationsFactory|SwatchFactory $object
     * @return void
     */
    private function _save($object)
    {
        $object->save();
    }

    /**
     * Delete Object
     *
     * @param VariationsFactory|SwatchFactory $object
     * @return void
     */
    private function _delete($object)
    {
        $object->delete();
    }

    /**
     * Delete old data
     *
     * @param int $productId
     * @return void
     */
    private function deletePreviousData($productId)
    {
        $collection = $this->variationFactory->create()
                                        ->getCollection()
                                        ->addFieldToFilter("product_id", $productId);
        foreach ($collection as $model) {
            $this->_delete($model);
        }
    }

    /**
     * Delete swatch data
     *
     * @param int $productId
     * @return void
     */
    private function deleteSwatchData($productId)
    {
        $collection = $this->swatchFactory->create()
                                        ->getCollection()
                                        ->addFieldToFilter("product_id", $productId);
        foreach ($collection as $model) {
            $this->_delete($model);
        }
    }

    /**
     * @param $value
     * @return false|int
     */
    private function isNumericStrict($value) {
        return is_string($value) || is_int($value) || is_float($value)
            ? preg_match('/^\d+(\.\d+)?$/', $value)
            : false;
    }
}
