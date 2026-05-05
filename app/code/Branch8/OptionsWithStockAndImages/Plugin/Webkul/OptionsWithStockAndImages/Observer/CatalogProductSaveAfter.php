<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\OptionsWithStockAndImages\Plugin\Webkul\OptionsWithStockAndImages\Observer;

use Exception;
use Magento\Framework\Event\ObserverInterface;
use Webkul\OptionsWithStockAndImages\Helper\Data;
use Webkul\OptionsWithStockAndImages\Model\ResourceModel\Swatch\CollectionFactory as SwatchCollection;
use Webkul\OptionsWithStockAndImages\Model\ResourceModel\Variations\CollectionFactory as VariationsCollection;
use Webkul\OptionsWithStockAndImages\Model\VariationsFactory;
use Webkul\OptionsWithStockAndImages\Model\SwatchFactory;
use Magento\Catalog\Model\Product\Type;
use Branch8\MarketplaceStaging\Helper\Data as MarketplaceStagingHelper;
use Branch8\OptionsWithStockAndImages\Model\Actions\IsVirtualProduct;

class CatalogProductSaveAfter
{
    /**
     * @var \Webkul\OptionsWithStockAndImages\Model\VariationsFactory
     */
    public $variationFactory;

    /**
     * @var \Branch8\OptionsWithStockAndImages\Helper\Salable
     */
    protected $salable;

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
     * @var \Magento\Backend\Model\Session
     */
    public $session;

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
     * @var Data
     */
    public $helper;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $_stockRegistry;

    /**
     * @var MarketplaceStagingHelper
     */
    protected $marketplaceStagingHelper;

    /**
     * Constructor
     *
     * @param \Branch8\OptionsWithStockAndImages\Helper\Salable $salable
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
     * @param \Magento\Backend\Model\Session $session
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param Data $helper
     * @param MarketplaceStagingHelper $marketplaceStagingHelper
     */
    public function __construct(
        \Branch8\OptionsWithStockAndImages\Helper\Salable         $salable,
        \Magento\Framework\App\RequestInterface                   $request,
        \Magento\Framework\Filesystem\Io\File                     $io,
        \Magento\Framework\Filesystem\DirectoryList               $directoryList,
        \Magento\MediaStorage\Helper\File\Storage\Database        $coreFileStorageDatabase,
        \Magento\Framework\Filesystem                             $filesystem,
        \Webkul\OptionsWithStockAndImages\Model\VariationsFactory $variationsFactory,
        \Webkul\OptionsWithStockAndImages\Model\SwatchFactory     $swatchFactory,
        \Magento\Framework\Filesystem\Driver\File                 $filDriver,
        \Webkul\OptionsWithStockAndImages\Logger\Logger           $logger,
        SwatchCollection                                          $swatchCollection,
        VariationsCollection                                      $variationCollection,
        \Magento\Backend\Model\Session                            $session,
        \Magento\CatalogInventory\Api\StockRegistryInterface      $stockRegistry,
        Data                                                      $helper,
        MarketplaceStagingHelper                                  $marketplaceStagingHelper
    ) {
        $this->salable = $salable;
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
        $this->session = $session;
        $this->_stockRegistry = $stockRegistry;
        $this->helper = $helper;
        $this->mediaDirectory = $filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        $this->marketplaceStagingHelper = $marketplaceStagingHelper;
    }

    public function aroundExecute(
        \Webkul\OptionsWithStockAndImages\Observer\CatalogProductSaveAfter $subject,
        \Closure                                                           $proceed,
        \Magento\Framework\Event\Observer                                  $observer
    )
    {
        try {
            $flag = false;
            $deleteData = false;
            $isAllowedTitle = true;
            $optionsCount = 0;
            $dirList = $this->directoryList->getPath('media');
            if (!$this->filDriver->isExists($dirList . '/wkosi/products')) {
                $this->filDriver->createDirectory($dirList . '/wkosi/products', 0775);
            }

            $product = $observer->getEvent()->getProduct();
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
            $optionValueTitleChange = [];
            if ($product->getOptions()) {
                foreach ($product->getOptions() as $option) {
                    $optionIdsArr[] = $option->getOptionId();
                    $optionData = $option->getData();
                    $otpType = $option->getType();
                    if ($otpType == "drop-down" || $otpType == "drop_down" || $otpType == "radio") {
                        $flag = true;
                        $title = [];
                        if (isset($optionData['values']) && !empty($optionData['values'])) {
                            foreach ($optionData['values'] as $optionValue) {
                                $title[] = $optionValue['title'];
                                if (!empty($optionValue['default_title']) && $optionValue['default_title'] != $optionValue['title']) {
                                    $optionValueTitleChange[$optionValue['default_title']] = $optionValue['title'];
                                }
                            }
                        }
                        $swatchVariationTitleArr[] = $title;
                    } else {
                        $flag = false;
                        $deleteData = true;
                        break;
                    }
                }
            }

            if ($deleteData) {
                $this->deleteSwatchData($productRowId);
                $this->deletePreviousData($productRowId);
            }
            if ($flag && $isAllowedTitle) {
                if ($product->getData('wk_manage_variation') ||
                    $product->getData('wk_manage_swatch')) {
                    if ($product->getData('wk_manage_variation')) {
                        $variationData = [];
                        if (is_string($product->getData('wk_manage_variation'))) {
                            $variationData = $this->buildVariation($product->getData('wk_manage_variation'));
                        } else {
                            $variationData = $product->getData('wk_manage_variation');
                        }
                        if (count($variationData)) {
                            $this->deletePreviousDataByTransaction($productRowId);
                            $this->saveVariationArray($variationData, $productRowId, $productId);
                        }
                    }

                    if ($product->getData('wk_manage_swatch')) {
                        $swatchData = [];
                        if (is_string($product->getData('wk_manage_swatch'))) {
                            $swatchData = $this->buildSwatch($product->getData('wk_manage_swatch'));
                        } else {
                            $swatchData = $product->getData('wk_manage_swatch');
                        }
                        if (count($swatchData)) {
                            $this->deleteSwatchData($productRowId);
                            $this->saveSwatchArray($swatchData, $productRowId, $optionIdsArr);
                        }
                    }
                } else {
                    if (isset($this->request->getParam('product')['wk_manage_variation'])) {
                        $variationDataJson = $this->request->getParam('product')['wk_manage_variation'];
                        if ($variationDataJson) {
                            $this->deletePreviousDataByTransaction($productRowId);
                            $variationDataArr = $this->buildVariation($variationDataJson);
                            $this->saveVariationData($variationDataArr, $productRowId, $productId);
                        }
                    } elseif (!empty($optionValueTitleChange)) {
                        $productVariations = $this->variationsFactory->create()->getCollection()
                            ->addFieldToFilter('product_id', $productRowId);
                        if (count($productVariations) > 0) {
                            foreach ($productVariations as $variation) {
                                $comb = $variation->getComb();
                                foreach ($optionValueTitleChange as $oldTitle => $newTitle) {
                                    if (strpos($comb, '_') !== false) {
                                        $combParts = explode('_', $comb);
                                        foreach ($combParts as $index => $part) {
                                            if ($part == $oldTitle) {
                                                $combParts[$index] = $newTitle;
                                            }
                                        }
                                        $comb = implode('_', $combParts);
                                    } elseif ($comb == $oldTitle) {
                                        $comb = $newTitle;
                                    }
                                }
                                $variation->setComb($comb);
                                $this->_save($variation);
                            }
                        }
                    }
                    if (isset($this->request->getParam('product')['wk_manage_swatch'])) {
                        $swatchDataJson = $this->request->getParam('product')['wk_manage_swatch'];
                        if ($swatchDataJson) {
                            $this->deleteSwatchData($productRowId);
                            $swatchDataArr = $this->helper->jsonDecode($swatchDataJson);
                            $this->saveSwatchData($swatchDataArr, $productRowId, $optionIdsArr);
                        }
                    }
                }

                // Hotai Customization: Auto-sync variations for Ticket products if not provided in request (e.g. during ticket import)
                if (IsVirtualProduct::check($product) && !isset($this->request->getParam('product')['wk_manage_variation'])) {
                    $this->syncTicketVariations($product);
                }

                if ($productType == Type::TYPE_SIMPLE) {
                    $this->setWeight($productRowId, $product->getWeight());
                } else {
                    $this->setWeight($productRowId, null);
                }
            }
            // Force update manage stock for product have variations
            //$this->changeManageStock($product);
            //$this->salable->changeCostForVariation($product);

            if (!$isAllowedTitle) {
                $this->disableAllSwatches($productRowId);
            }
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
        return $this;
    }

    public function changeManageStock($product)
    {
        $this->salable->changeManageStock($product);
    }

    public function buildSwatch($value)
    {
        $swatchDataArr = json_decode($value, true);
        $wkswatch = [];
        $data = [];
        foreach ($swatchDataArr as $swatchData) {
            $var = ltrim($swatchData['name'], "wkswatch");
            $var = ltrim($var, "[");
            $var = rtrim($var, "]");
            $var = explode("][", $var);
            $wkswatch[$var[0]][$var[1]] = $swatchData['value'];
        }
        foreach ($wkswatch as $keyId => $swatch) {
            if (isset($swatch['is_swatch'])
                &&
                ($swatch['is_swatch'] == "on" || $swatch['is_swatch'] == 1 || $swatch['is_swatch'] == true)
            ) {
                $swatch['is_swatch'] = 1;
            } else {
                $swatch['is_swatch'] = 0;
            }
            $data[] = $swatch;
        }
        return $data;
    }

    public function buildVariation($value)
    {
        $variationTemp = json_decode($value, true);
        $wkvariation = [];
        $data = [];
        foreach ($variationTemp as $variationData) {
            if(!isset($variationData['value'])){
                continue;
            }
            $var = ltrim($variationData['name'], "wkvariation");
            $var = ltrim($var, "[");
            $var = rtrim($var, "]");
            $var = explode("][", $var);
            if ($var[1] == 'file' || $var[1] == 'image') {
                if (isset($var[2]) && $var[2] !== '') {
                    $wkvariation[$var[0]][$var[1]][$var[2]] = $variationData['value'];
                } else {
                    $wkvariation[$var[0]][$var[1]] = $variationData['value'];
                }
            } else {
                $wkvariation[$var[0]][$var[1]] = $variationData['value'];
            }
        }
        foreach ($wkvariation as $variation) {
            $variationImage = [];
            if (!empty($variation['file'])) {
                foreach ($variation['file'] as $key => $file) {
                    $variationImage[$key] = rtrim($file, ".tmp");
                }
            }
            $variation['image'] = (!empty($variation['image']) && empty($variationImage)) ? $variation['image'] : implode(',', $variationImage);
            if (!isset($variation['is_sync'])) {
                $variation['is_sync'] = '0';
            }
            $data[] = $variation;
        }
        return $data;
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
    )
    {
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
            if ($var[1] == 'comb' && $this->helper->pregMatch($variationData['value'])) {
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

    /**
     * @param array $wkvariation
     * @param $productId
     * @return void
     */
    public function saveVariationArrayByTransaction(array $wkvariation, $productId)
    {
        $insertData = [];
        foreach ($wkvariation as $variation) {
            $variationImage = [];
            if (!empty($variation['file'])) {
                foreach ($variation['file'] as $key => $file) {
                    $variationImage[$key] = rtrim($file, ".tmp");
                    if ($variationImage[$key] && strpos($file, '.tmp') !== false) {
                        $this->saveFile($variationImage[$key]);
                    }
                }
            }
            $variation['image'] = (!empty($variation['image']) && empty($variationImage)) ? $variation['image'] : $variationImage;
            if (is_array($variation['image'])) {
                $variation['image'] = implode(',', array_unique(array_filter($variation['image'])));
            }
            $variation['product_id'] = $productId;
            unset($variation['file']);
            $insertData[] = $variation;
            /**
             *
             */
        }
        if ($insertData) {
            $connection = $this->variationsFactory->create()->getResource()->getConnection();
            $connection->insertMultiple('wk_osi_variations', $insertData);
        }
    }

    /**
     * @param array $wkvariation
     * @param $productId
     * @param $mageProductId
     * @return void
     */
    public function saveVariationArray(array $wkvariation, $productId,$mageProductId)
    {
        foreach ($wkvariation as $variation) {
            $variationImage = [];
            if (!empty($variation['file'])) {
                foreach ($variation['file'] as $key => $file) {
                    $variationImage[$key] = rtrim($file, ".tmp");
                    if ($variationImage[$key] && strpos($file, '.tmp') !== false) {
                        $this->saveFile($variationImage[$key]);
                    }
                }
            }
            $variation['image'] = (!empty($variation['image']) && empty($variationImage)) ? $variation['image'] : $variationImage;
            if (is_array($variation['image'])) {
                $variation['image'] = implode(',', array_unique(array_filter($variation['image'])));
            }
            $variation['product_id'] = $productId;
            $variation['mageproduct_id'] = $mageProductId;
            $variationModel = $this->variationsFactory->create()
                ->setData($variation);
            $this->_save($variationModel);
        }
    }

    /**
     * Save Variation Data
     *
     * @param array $variationDataArr
     * @param int $productId
     * @return void
     */
    private function saveVariationData(array $variationDataArr, $productId, $mageProductId)
    {
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
            $this->saveVariationArray($wkvariation, $productId, $mageProductId);

        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }

    public function saveSwatchArray($wkswatch, $productId, $optionIdsArr)
    {
        foreach ($wkswatch as $keyId => $swatch) {
            $swatch['product_id'] = $productId;
            if (isset($swatch['is_swatch'])
                &&
                ($swatch['is_swatch'] == "on" || $swatch['is_swatch'] == 1 || $swatch['is_swatch'] == true)
            ) {
                $swatch['is_swatch'] = 1;
            } else {
                $swatch['is_swatch'] = 0;
            }

            $swatch['option_id'] = $optionIdsArr[$keyId];
            try {
                $swatchModel = $this->swatchFactory->create()->setData($swatch);
                $this->_save($swatchModel);
            } catch (\Exception $e) {
                $this->logger->info($e->getMessage());
            }
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
            $this->logger->info($e->getMessage());
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
        $baseTmpImagePath = $this->getFilePath($dirList . '/tmp/catalog/product', $fileName);
        $baseImagePath = $this->getFilePath($dirList . '/wkosi/products', $fileName);
        if ($this->mediaDirectory->isExist($baseTmpImagePath) &&
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
     * @param $productId
     * @return void
     * @throws Exception
     */
    private function deletePreviousDataByTransaction($productId)
    {
        $this->logger->info("deletePreviousDataByTransaction:$productId");

        $connection = $this->variationsFactory->create()
            ->getResource()->getConnection();
        try {
            $connection->beginTransaction();
            $where=['product_id = ?' => $productId];
            $connection->delete('wk_osi_variations', $where);
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }

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
    )
    {
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
    /**
     * Synchronize variations for ticket products based on custom options.
     * Required for programmatic ticket imports.
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return void
     */
    protected function syncTicketVariations($product)
    {
        $this->marketplaceStagingHelper->syncTicketVariations($product);
    }
}
