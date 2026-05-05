<?php
namespace Branch8\MarketplaceStaging\Helper;

use Webkul\OptionsWithStockAndImages\Helper\Data;
use Webkul\OptionsWithStockAndImages\Model\ResourceModel\Swatch\CollectionFactory as SwatchCollection;
use Webkul\OptionsWithStockAndImages\Model\ResourceModel\Variations\CollectionFactory as VariationsCollection;
use Webkul\OptionsWithStockAndImages\Model\VariationsFactory;
use Webkul\OptionsWithStockAndImages\Model\SwatchFactory;
use Magento\Catalog\Model\Product\Type;

/**
 * Webkul Marketplace Helper Data.
 */
class Variation extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    public $filDriver;

    /**
     * @var SwatchCollection
     */
    public $swatchCollection;

    /**
     * @var VariationsCollection
     */
    public $variationCollection;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    public $request;

    /**
     * @var \Magento\Framework\Filesystem\Io\File
     */
    public $io;

    /**
     * @var \Webkul\OptionsWithStockAndImages\Logger\Logger
     */
    public $logger;

    /**
     * @var \Magento\Framework\Filesystem\DirectoryList
     */
    public $directoryList;

    /**
     * @var \Webkul\OptionsWithStockAndImages\Model\VariationsFactory
     */
    public $variationsFactory;
     /**
      * @var \Webkul\OptionsWithStockAndImages\Model\SwatchFactory
      */
    public $swatchFactory;

    /**
     * @var \Magento\MediaStorage\Helper\File\Storage\Database
     */
    public $coreFileStorageDatabase;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    public $mediaDirectory;

    /**
     * @var Magento\Catalog\Model\ProductFactory
     */
    public $productFactory;

    /**
     * @var Data
     */
    public $helper;

    /**
     * @var \Webkul\Marketplace\Helper\Data
     */
    protected $helperSeller;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $_stockRegistry;

    /**
     * @var \Branch8\OptionsWithStockAndImages\Helper\Salable
     */
    protected $salable;

    /**
     * Constructor
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Filesystem\Io\File $io
     * @param \Magento\Framework\Filesystem\DirectoryList $directoryList
     * @param \Magento\MediaStorage\Helper\File\Storage\Database $coreFileStorageDatabase
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Webkul\OptionsWithStockAndImages\Model\VariationsFactory $variationsFactory
     * @param \Webkul\OptionsWithStockAndImages\Model\SwatchFactory $swatchFactory
     * @param \Magento\Framework\Filesystem\Driver\File $filDriver
     * @param \Webkul\OptionsWithStockAndImages\Logger\Logger $logger
     * @param SwatchCollection $swatchCollection
     * @param VariationsCollection $variationCollection
     * @param Data $helper
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Filesystem\Io\File $io,
        \Magento\Framework\Filesystem\DirectoryList $directoryList,
        \Magento\MediaStorage\Helper\File\Storage\Database $coreFileStorageDatabase,
        \Magento\Framework\Filesystem $filesystem,
        \Webkul\OptionsWithStockAndImages\Model\VariationsFactory $variationsFactory,
        \Webkul\OptionsWithStockAndImages\Model\SwatchFactory $swatchFactory,
        \Magento\Framework\Filesystem\Driver\File $filDriver,
        \Webkul\OptionsWithStockAndImages\Logger\Logger $logger,
        SwatchCollection $swatchCollection,
        VariationsCollection $variationCollection,
        \Magento\Catalog\Model\ProductFactory $product,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        Data $helper,
        \Webkul\Marketplace\Helper\Data $helperSeller,
        \Branch8\OptionsWithStockAndImages\Helper\Salable $salable
    ) {
        $this->filDriver = $filDriver;
        $this->request = $request;
        $this->io = $io;
        $this->logger = $logger;
        $this->directoryList = $directoryList;
        $this->variationsFactory = $variationsFactory;
        $this->swatchFactory = $swatchFactory;
        $this->coreFileStorageDatabase = $coreFileStorageDatabase;
        $this->swatchCollection = $swatchCollection;
        $this->variationCollection = $variationCollection;
        $this->productFactory = $product;
        $this->_stockRegistry = $stockRegistry;
        $this->helper = $helper;
        $this->helperSeller = $helperSeller;
        $this->salable = $salable;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
    }

    public function syncNeedToRefillVariation($productId)
    {
        $this->salable->syncNeedToRefill([$productId]);
    }

    public function saveVariationWhenApprove($product, $sellerId){
        try {
            $flag = false;
            $deleteData = false;
            if(!$product->getId()){
                return;
            }

            if ($product->getOptions()) {
                $optionsCount = count($product->getOptions());
                $isNotRequiredCount = 0;
                foreach ($product->getOptions() as $option) {
                    if (!$option->getIsRequire()) {
                        $isNotRequiredCount++;
                    }
                }
                if ($optionsCount == $isNotRequiredCount) {
                    $deleteData = true;
                }
            } else {
                $deleteData = true;
            }
            $productId = $product->getId();
            $productRowId = $product->getRowId();
            $optionIdsArr = [];
            $swatchVariationTitleArr = [];
            foreach ($product->getOptions() as $option) {
                $optionIdsArr[] = $option->getOptionId();
                $optionData = $option->getData();
                $otpType = $option->getType();
                if ($otpType=="drop-down" || $otpType=="drop_down" || $otpType=="radio") {
                    $flag = true;
                    $title = [];
                    if (isset($optionData['values']) && !empty($optionData['values'])) {
                        foreach ($optionData['values'] as $optionValue) {
                            $title[] = $optionValue['title'];
                        }
                    }
                    $swatchVariationTitleArr[] = $title;
                } else {
                    $flag = false;
                    $deleteData = true;
                    break;
                }
            }

            // Validation and remove old Variation when remove + add new custom option
            $swatchData = [];
            if($product->getData('wk_manage_swatch')){
                $swatchData = $product->getData('wk_manage_swatch');
            }
            if(count($swatchData) && count($swatchData) != $optionsCount){
                $flag = false;
                $deleteData = true;
            }

            // Validation and remove old Variation when remove + add new custom option value
            if(count($swatchVariationTitleArr)){
                $variationCount = $this->variationCollection->create()->addFieldToFilter('product_id', $productRowId)->getSize();
                if($variationCount){
                    $variationCom = $this->salable->combineArrays($swatchVariationTitleArr);
                    if($variationCount != count($variationCom)){
                        $deleteData = true;
                    }
                }
            }

            // for saving duplicate product`s option in swatches table
            $swatchesCollection = $this->swatchCollection->create()->addFieldToFilter('product_id', $productRowId);
            $i = 0;
            if (!empty($optionIdsArr)) {
                foreach ($swatchesCollection as $swatch) {
                    $swatch->setOptionId($optionIdsArr[$i]);
                    $this->_save($swatch);
                    $i++;
                }
            }
            // Check total swatch and current custom option
            if($i != $optionsCount){
                $deleteData = true;
            }

            if ($deleteData) {
                $this->deleteSwatchData($productRowId);
                $this->deletePreviousData($productRowId);
            }

            if ($flag) {
                if ($product->getData('wk_manage_variation')) {
                    $variationData = $product->getData('wk_manage_variation');
                    if (count($variationData)) {
                        $this->deletePreviousData($productRowId);
                        $this->saveVariationArray($variationData, $productRowId, $sellerId);
                    }
                }
                if ($product->getData('wk_manage_swatch')) {
                    $swatchData = $product->getData('wk_manage_swatch');
                    if (count($swatchData)) {
                        $this->deleteSwatchData($productRowId);
                        $this->saveSwatchArray($swatchData, $productRowId, $optionIdsArr);
                    }
                }
            }

            $this->changeManageStock($product);

        } catch (\Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_MarketplaceStaging', 'variantions')){
                $this->logger->info($e->getMessage());
            }
        }
        return $this;
    }

    public function changeManageStock($product){
        $this->salable->changeManageStock($product);
    }

    /**
     * saveVariation
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function saveVariation($product_id, $requestData)
    {
        try {
            $flag = false;
            $deleteData = false;
            $isAllowedTitle = true;
            $dirList = $this->directoryList->getPath('media');
            if (!$this->filDriver->isExists($dirList.'/wkosi/products')) {
                $this->filDriver->createDirectory($dirList.'/wkosi/products', 0775);
            }

            $product = $this->getProduct($product_id);
            if(!$product->getId()){
                return;
            }


            if ($product->getOptions()) {
                $optionsCount = count($product->getOptions());
                $isNotRequiredCount = 0;
                foreach ($product->getOptions() as $option) {
                    if (!$option->getIsRequire()) {
                        $isNotRequiredCount++;
                    }
                }
                if ($optionsCount == $isNotRequiredCount) {
                    $deleteData = true;
                }
            } else {
                $deleteData = true;
            }
            $productType = $product->getTypeId();
            $productId = $product->getId();
            $productRowId = $product->getRowId();

            $optionIdsArr = [];
            $swatchVariationTitleArr = [];
            foreach ($product->getOptions() as $option) {
                $optionIdsArr[] = $option->getOptionId();
                $optionData = $option->getData();
                $otpType = $option->getType();
                if ($otpType=="drop-down" || $otpType=="drop_down" || $otpType=="radio") {
                    $flag = true;
                    $title = [];
                    if (isset($optionData['values']) && !empty($optionData['values'])) {
                        foreach ($optionData['values'] as $optionValue) {
                            $title[] = $optionValue['title'];
                        }
                    }
                    $swatchVariationTitleArr[] = $title;
                } else {
                    $flag = false;
                    $deleteData = true;
                    break;
                }
            }

            // Validation and remove old Variation when remove + add new custom option
            $swatchData = [];
            if (isset($requestData['product']['wk_manage_swatch'])) {
                $swatchDataJson = $requestData['product']['wk_manage_swatch'];
                if ($swatchDataJson) {
                    $swatchData = $this->helper->jsonDecode($swatchDataJson);
                }
            }
            if(count($swatchData) && count($swatchData) != $optionsCount){
//                $flag = false;
                $deleteData = true;
            }

            // Validation and remove old Variation when remove + add new custom option value
            if(count($swatchVariationTitleArr)){
                $variationCount = $this->variationCollection->create()->addFieldToFilter('product_id', $productRowId)->getSize();
                if($variationCount){
                    $variationCom = $this->salable->combineArrays($swatchVariationTitleArr);
                    if($variationCount != count($variationCom)){
                        $deleteData = true;
                    }
                }
            }

            if ($deleteData) {
                $this->deleteSwatchData($productRowId);
                $this->deletePreviousData($productRowId);
            }
            // for saving duplicate product`s option in swatches table
            $swatchesCollection = $this->swatchCollection->create()->addFieldToFilter('product_id', $productRowId);
            $i = 0;
            if (!empty($optionIdsArr)) {
                foreach ($swatchesCollection as $swatch) {
                    $swatch->setOptionId($optionIdsArr[$i]);
                    $this->_save($swatch);
                    $i++;
                }
            }

            if ($flag && $isAllowedTitle) {
                if (isset($requestData['product']['wk_manage_variation'])) {
                    $variationDataJson = $requestData['product']['wk_manage_variation'];
                    if ($variationDataJson) {
                        $this->deletePreviousData($productRowId);
                        $variationDataArr = $this->helper->jsonDecode($variationDataJson);
                        $this->saveVariationData($variationDataArr, $productRowId, $productType);
                    }
                }
                if (isset($requestData['product']['wk_manage_swatch'])) {
                    $swatchDataJson = $requestData['product']['wk_manage_swatch'];
                    if ($swatchDataJson) {
                        $this->deleteSwatchData($productRowId);
                        $swatchDataArr = $this->helper->jsonDecode($swatchDataJson);
                        $this->saveSwatchData($swatchDataArr, $productRowId, $optionIdsArr);
                    }
                }
                if ($productType != $this->request->getParam('type')) {
                    if ($productType == Type::TYPE_SIMPLE) {
                        $this->setWeight($productId, $product->getWeight());
                    } else {
                        $this->setWeight($productId, null);
                    }
                }
            }

            $this->changeManageStock($product);

            if (!$isAllowedTitle) {
                $this->disableAllSwatches($productRowId);
            }

        } catch (\Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_MarketplaceStaging', 'variantions')){
                $this->logger->info($e->getMessage());
            }
        }
        return $this;
    }

    /**
     * Get Product by Id.
     *
     * @param int $productId
     *
     * @return object
     */
    public function getProduct($productId)
    {
        return $this->productFactory->create()->load($productId);
    }

    /**
     * Check Is Allowed Title
     *
     * @param string $title
     * @param array $swatchVariationTitleArr
     * @param boolean $isAllowedTitle
     * @return boolean
     */
    public function checkIsAllowedTitle(
        $title,
        $swatchVariationTitleArr,
        $isAllowedTitle
    ) {
        if (!$isAllowedTitle) {
            return false;
        } elseif (in_array($title, $swatchVariationTitleArr) || $this->helper->pregMatch($title)) {
            return false;
        }
        return true;
    }

     /**
      * Disable All Swatches
      *
      * @param int $productId
      * @return void
      */
    public function disableAllSwatches($productId)
    {
        $productSwatches = $this->swatchFactory->create()->getCollection()
                                ->addFieldToFilter('product_id', $productId);
        if (count($productSwatches) > 0) {
            foreach ($productSwatches as $swatch) {
                $this->swatchFactory->create()
                                    ->load($swatch->getId())
                                    ->setIsSwatch(0)
                                    ->save();
            }
        }
    }
     /**
      * Check If Has Special Character In Variation
      *
      * @param array $variationDataArr
      * @return boolean
      */
    public function checkIfHasSpecialCharacterInVariation($variationDataArr)
    {
        foreach ($variationDataArr as $variationData) {
            $var = ltrim($variationData['name'], "wkvariation");
            $var = ltrim($var, "[");
            $var = rtrim($var, "]");
            $var = explode("][", $var);
            if ($var[1]=='comb' && $this->helper->pregMatch($variationData['value'])) {
                return true;
            }
        }
        return false;
    }

     /**
      * Check If Has Special Character In Swatch
      *
      * @param array $swatchDataArr
      * @return boolean
      */
    public function checkIfHasSpecialCharacterInSwatch($swatchDataArr)
    {
        foreach ($swatchDataArr as $swatchData) {
            $var = ltrim($swatchData['name'], "wkswatch");
            $var = ltrim($var, "[");
            $var = rtrim($var, "]");
            $var = explode("][", $var);
            if ($var[1]=='title' && $this->helper->pregMatch($swatchData['value'])) {
                return true;
            }
        }
        return false;
    }

    public function saveVariationArray(array $wkvariation, $productId, $sellerId){
        foreach ($wkvariation as $variation) {

            $skuProductId = $this->productFactory->create()->getIdBySku($variation['sku']);
            if($skuProductId){
                $productSellerId = $this->helperSeller->getSellerIdByProductId($skuProductId);
                if($sellerId != $productSellerId)
                {
                    // sku option not belong to seller
                    continue;
                }
            }

            try{
                $variationImage = [];
                if (!empty($variation['file'])) {
                    foreach ($variation['file'] as $key => $file) {
                        $variationImage[$key] = rtrim($file, ".tmp");
                        if ($variationImage[$key] && strpos($file, '.tmp') !== false) {
                            $this->saveFile($variationImage[$key]);
                        }
                    }
                }
                $variation['image'] = implode(',', $variationImage);
                $variation['product_id'] = $productId;
                $variationModel = $this->variationsFactory->create()
                                        ->setData($variation);
                $this->_save($variationModel);
            } catch (\Exception $e){
                if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_MarketplaceStaging', 'variantions')){
                    $this->logger->info($e->getMessage());
                }
            }

        }
    }

    /**
     * Save Variation Data
     *
     * @param array $variationDataArr
     * @param int $productId
     * @return void
     */
    private function saveVariationData(array $variationDataArr, $productId)
    {
        $sellerId = $this->helperSeller->getCustomerId();
        try {
            $wkvariation = [];
            foreach ($variationDataArr as $variationData) {
                $var = ltrim($variationData['name'], "wkvariation");
                $var = ltrim($var, "[");
                $var = rtrim($var, "]");
                $var = explode("][", $var);
                if ($var[1] == 'file') {
                    if ($var[2] != '') {
                        $wkvariation[$var[0]][$var[1]][$var[2]] = $variationData['value'];
                    }
                } else {
                    $wkvariation[$var[0]][$var[1]] = $variationData['value'];
                }
            }
            $this->saveVariationArray($wkvariation, $productId, $sellerId);

        } catch (\Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_MarketplaceStaging', 'variantions')){
                $this->logger->info($e->getMessage());
            }
        }
    }

    public function saveSwatchArray($wkswatch, $productId, $optionIdsArr){
        foreach ($wkswatch as $keyId => $swatch) {
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
            $swatchModel = $this->swatchFactory->create()
                                    ->setData($swatch);
            $this->_save($swatchModel);
        }
    }

    /**
     * Save Swatch Data
     *
     * @param array $swatchDataArr
     * @param int $productId
     * @param array $optionIdsArr
     * @return void
     */
    private function saveSwatchData($swatchDataArr, $productId, $optionIdsArr)
    {
        try {
            $wkswatch = [];
            foreach ($swatchDataArr as $swatchData) {
                $var = ltrim($swatchData['name'], "wkswatch");
                $var = ltrim($var, "[");
                $var = rtrim($var, "]");
                $var = explode("][", $var);
                $wkswatch[$var[0]][$var[1]] = $swatchData['value'];
            }
            $this->saveSwatchArray($wkswatch, $productId, $optionIdsArr);
        } catch (\Exception $e) {
            if(\Branch8\HotaiCore\Helper\DebugLog::isEnable('Branch8_MarketplaceStaging', 'variantions')){
                $this->logger->info($e->getMessage());
            }
        }
    }

    /**
     * Get Image Path
     *
     * @param string $path
     * @param string $imageName
     * @return string
     */
    public function getFilePath($path, $imageName)
    {
        return rtrim($path, '/') . '/' . ltrim($imageName, '/');
    }

    /**
     * Move file from tmp to wkosi directory
     *
     * @param string $fileName
     * @return void
     */
    public function saveFile($fileName)
    {
        $dirList = $this->directoryList->getPath('media');
        $baseTmpImagePath = $this->getFilePath($dirList.'/tmp/catalog/product', $fileName);
        $baseImagePath = $this->getFilePath($dirList.'/wkosi/products', $fileName);
        if($this->mediaDirectory->isExist($baseTmpImagePath) &&
            !$this->mediaDirectory->isExist($baseImagePath)) {
            $this->mediaDirectory->copyFile(
                $baseTmpImagePath,
                $baseImagePath
            );
        }
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
        $collection = $this->variationsFactory->create()
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
     * Set weight
     *
     * @param int $productId
     * @param int|null $weight
     * @return void
     */
    private function setWeight($productId, $weight)
    {
        $collection = $this->variationsFactory->create()
                                        ->getCollection()
                                        ->addFieldToFilter("product_id", $productId);
        foreach ($collection as $model) {
            $model->setWeight($weight);
            $this->_save($model);
        }
    }
    /**
     * Setting data for the variation
     *
     * @param int $duplicateProductId
     * @param VariationsCollection $variationsCollection
     * @param SwatchCollection $swatchesCollection
     *
     * @return array
     */
    private function saveVariationAndSwatchesForDuplicateProduct(
        $duplicateProductId,
        $variationsCollection,
        $swatchesCollection
    ) {
        try {
            // saving variations for duplicate product

            foreach ($variationsCollection as $variation) {
                $variationFactory = $this->variationsFactory->create();
                $variationFactory->setWeight($variation->getWeight());
                $variationFactory->setComb($variation->getComb());
                $variationFactory->setImage($variation->getImage());
                $variationFactory->setStock($variation->getStock());
                $variationFactory->setProductId($duplicateProductId);
                $this->_save($variationFactory);
            }
            foreach ($swatchesCollection as $swatch) {
                $swatchesFactory = $this->swatchFactory->create();
                $swatchesFactory->setOptionId($swatch->getOptionId());
                $swatchesFactory->setTitle($swatch->getTitle());
                $swatchesFactory->setIsSwatch($swatch->getIsSwatch());
                $swatchesFactory->setProductId($duplicateProductId);
                $this->_save($swatchesFactory);
            }
            return ['error' => 0, 'message' => 'Swatches and variation created successfully'];
        } catch (\Exception $e) {
            return ['error' => 1, 'message' => $e->getMessage()];
        }
    }

    public function cloneVariationData($originalProductRowId, $duplicateProductRowId, $duplicateProductId){
        $swatchesCollection = $this->swatchCollection->create()->addFieldToFilter('product_id', $originalProductRowId);
        $variationsCollection = $this->variationCollection->create()->addFieldToFilter('product_id', $originalProductRowId);
        if ($swatchesCollection->getSize() != 0 || $variationsCollection->getSize() != 0) {
            try {
                // saving variations for duplicate product
                foreach ($variationsCollection as $variation) {
                    $variationFactory = $this->variationsFactory->create();
                    $variationFactory->setWeight($variation->getWeight());
                    $variationFactory->setComb($variation->getComb());
                    $variationFactory->setImage($variation->getImage());
                    $variationFactory->setStock($variation->getStock());
                    $variationFactory->setProductId($duplicateProductRowId);
                    $variationFactory->setSku($variation->getSku());
                    $variationFactory->setIsSync($variation->getIsSync());
                    $variationFactory->setIsLockSku($variation->getIsLockSku());
                    $variationFactory->setProductItemId($variation->getProductItemId());
                    $variationFactory->save();
                }
                foreach ($swatchesCollection as $swatch) {
                    $swatchesFactory = $this->swatchFactory->create();
                    $swatchesFactory->setOptionId($swatch->getOptionId());
                    $swatchesFactory->setTitle($swatch->getTitle());
                    $swatchesFactory->setIsSwatch($swatch->getIsSwatch());
                    $swatchesFactory->setProductId($duplicateProductRowId);
                    $swatchesFactory->save();
                }

                $stockItem = $this->_stockRegistry->getStockItem($duplicateProductId);
                $stockItem->setUseConfigManageStock(0);
                $stockItem->setManageStock(0);
                $stockItem->setQty(0);
                $stockItem->save();
                
            } catch (\Exception $e) {
            }
        }
    }
}
