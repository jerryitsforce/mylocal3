<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_OptionsWithStockAndImages
 * @author    Webkul Software Private Limited
 * @copyright Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Branch8\MarketplaceStaging\Block\CustomOption;

use Branch8\MarketplaceStaging\Helper\Data as MarketplaceStagingHelper;

class Variation extends \Magento\Backend\Block\Template
{
    /**
     * @var \Webkul\OptionsWithStockAndImages\Model\VariationsFactory
     */
    public $variationsFactory;

    /**
     * @var \Webkul\OptionsWithStockAndImages\Model\SwatchFactory
     */
    public $swatchFactory;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    public $_urlInterface;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManagerInterface;

    /**
     * @var \Webkul\OptionsWithStockAndImages\Helper\Data
     */
    public $helper;

    /**
     * @var MarketplaceStagingHelper
     */
    public $marketplaceStagingHelper;

    public $tempData;

    public $registry;
    private $savedData;

    /**
     * __construct
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\UrlInterface $urlInterface
     * @param \Webkul\OptionsWithStockAndImages\Model\VariationsFactory $variationsFactory
     * @param \Webkul\OptionsWithStockAndImages\Model\SwatchFactory $swatchFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Webkul\OptionsWithStockAndImages\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\UrlInterface $urlInterface,
        \Webkul\OptionsWithStockAndImages\Model\VariationsFactory $variationsFactory,
        \Webkul\OptionsWithStockAndImages\Model\SwatchFactory $swatchFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Webkul\OptionsWithStockAndImages\Helper\Data $helper,
        MarketplaceStagingHelper $marketplaceStagingHelper,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {

        $this->variationsFactory = $variationsFactory;
        $this->swatchFactory = $swatchFactory;
        $this->_urlInterface = $urlInterface;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->helper = $helper;
        $this->marketplaceStagingHelper = $marketplaceStagingHelper;
        $this->registry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Get Image Upload Url
     *
     * @return string
     */
    public function getImageUrl()
    {
        return $this->_urlInterface->getUrl('marketplacestaging/variation/upload');
    }

    /**
     * Get Image Upload Url
     *
     * @return string
     */
    public function getSyncUrl()
    {
        return $this->_urlInterface->getUrl('marketplacestaging/variation/getdatasync');
    }

    /**
     * Get Image Upload Url
     *
     * @return string
     */
    public function getCustomOptionUrl()
    {
        return $this->_urlInterface->getUrl('marketplacestaging/variation/getcustomoption');
    }

    /**
     * Check the module is enable or not
     *
     * @return bool
     */
    public function isModuleEnable()
    {
        return $this->helper->isEnable();
    }

    public function getProduct()
    {
        return $this->registry->registry('product');
    }

    /**
     * Get saved product data
     *
     * @return string
     */
    public function getSavedData($encode = true)
    {
        if ($this->savedData !== null) {
            return $this->savedData;
        }
        $this->savedData = '{}';
        $this->tempData = $this->getTempData();
        $keys = ['variation', 'wk_manage_variation'];
        if (is_array($this->tempData)) {
            foreach ($keys as $key) {
                if (array_key_exists($key, $this->tempData)) {
                    return $this->tempData[$key];
                }
            }
        }
        $product = $this->getProduct();
        if ($product->getRowId()) {
            $productId = $product->getRowId();
        } else {
            $productId = $this->getRequest()->getParam('id');
        }
        if ($productId) {
            $collection = $this->variationsFactory->create()
                ->getCollection()
                ->addFieldToFilter("product_id", $productId);
            if ($collection->getSize()) {
                $data = $collection->getData();
                foreach ($data as $key => $value) {
                    $data[$key]['image'] = explode(',', $data[$key]['image']);
                    if (empty($data[$key]['file']) && is_array($data[$key]['image'])) {
                        $data[$key]['file'] = $data[$key]['image'];
                    } else {
                        $data[$key]['file'] = [];
                    }
                }
                $this->savedData = $this->helper->jsonEncode($data);
            }
        }
        return $this->savedData;
    }

    /**
     * Get path of uploaded images
     *
     * @return string
     */
    public function getMediaUrl()
    {
        return $this->storeManagerInterface->getStore()
                    ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).'wkosi/products';
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getTmpMediaUrl()
    {
        return $this->storeManagerInterface->getStore()
                ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).'tmp/catalog/product';
    }

    /**
     * @param $encode
     * @return array|mixed|string|\Webkul\OptionsWithStockAndImages\Helper\jsonEncode|null
     */
    public function getSwatchData($encode = true)
    {
        if($this->tempData && array_key_exists('swatch', $this->tempData)){
            return $this->tempData['swatch'];
        }
        $product = $this->getProduct();
        if($product->getRowId()){
            $productId = $product->getRowId();
        } else {
            $productId = $this->getRequest()->getParam('id');
        }
        if ($productId) {
            $collection = $this->swatchFactory->create()
                                        ->getCollection()
                                        ->addFieldToFilter("product_id", $productId);
            if ($collection->getSize()) {
                return $encode ? $this->helper->jsonEncode($collection->getData()) : $collection->getData();
            }
        }
        return '{}';
    }

    /**
     * @return null
     */
    public function getTempData(){
        $this->tempData = null;
        $productTemp = null;
        $productId = $this->getRequest()->getParam('id');
        if($productId){
            $productTemp = $this->marketplaceStagingHelper->getProductTempDataRepositoryByProductId($productId);
        } elseif ($productId = $this->getRequest()->getParam('temp_id')) {
            $productTemp = $this->marketplaceStagingHelper->getProductTempDataRepositoryById($productId);
        }
        if($productId){
            if($productTemp){
                $productTemp = json_decode($productTemp->getInformation(), true);
                if(array_key_exists('options', $productTemp['product']) &&
                    is_array($productTemp['product']['options']) &&
                    !empty($productTemp['product']['options'])){
                    $options = $productTemp['product']['options'];
                    $optionIdsArr = [];
                    if (!empty($options)) {
                        foreach ($options as $customOptionData) {
                            $optionIdsArr[] = $customOptionData['option_id'];
                        }
                    }

                    if(array_key_exists('wk_manage_variation', $productTemp['product'])){
                        if(is_array($productTemp['product']['wk_manage_variation'])){
                            $this->tempData['variation'] = $this->buildVariationData($productTemp['product']['wk_manage_variation'], $productId);
                        } else {
                            $variationTemp = json_decode($productTemp['product']['wk_manage_variation'], true);
                            $this->tempData['variation'] = $this->buildVariationData($variationTemp, $productId);
                        }
                    }
                    if(array_key_exists('wk_manage_swatch', $productTemp['product'])){
                        if(is_array($productTemp['product']['wk_manage_swatch'])){
                            $this->tempData['swatch'] = $this->buildSwatchData($productTemp['product']['wk_manage_swatch'], $productId, $optionIdsArr);
                        } else {
                            $swatchTemp = json_decode($productTemp['product']['wk_manage_swatch'], true);
                            $this->tempData['swatch'] = $this->buildSwatchData($swatchTemp, $productId, $optionIdsArr);
                        }
                    }
                }
            }
        }
        return $this->tempData;
    }

    public function buildSwatchData($swatchDataArr, $productId, $optionIdsArr)
    {
        $wkswatch = [];
        $data = [];
        if(array_key_exists('name', $swatchDataArr[0])){
            foreach ($swatchDataArr as $swatchData) {
                $var = ltrim($swatchData['name'], "wkswatch");
                $var = ltrim($var, "[");
                $var = rtrim($var, "]");
                $var = explode("][", $var);
                $wkswatch[$var[0]][$var[1]] = $swatchData['value'];
            }
        } else {
            $wkswatch = $swatchDataArr;
        }

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
            $data[] = $swatch;
        }
        return json_encode($data);
    }

    public function buildVariationData($variationDataArr, $productId){
        $wkvariation = [];
        $data = [];
        if(array_key_exists('name', $variationDataArr[0])){
            foreach ($variationDataArr as $variationData) {
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
        } else {
            $wkvariation = $variationDataArr;
        }

        foreach ($wkvariation as $variation) {
            $variationImage = [];
            if (!empty($variation['file'])) {
                foreach ($variation['file'] as $key => $file) {
                    $variationImage[$key] = rtrim($file, ".tmp");
                }
            }
            $variation['image'] = (!empty($variation['image']) && empty($variationImage)) ? $variation['image'] : $variationImage;
            if (is_array($variation['image'])) {
                $variation['image'] = array_unique(array_filter($variation['image']));
            }
            $variation['product_id'] = $productId;
            $data[] = $variation;
        }
        return json_encode($data);
    }

    /**
     * @return bool
     */
    public function useManageVariant2()
    {
        return (bool)$this->_scopeConfig->getValue('marketplace/custom_options/use_manage_variant2');
    }
}
