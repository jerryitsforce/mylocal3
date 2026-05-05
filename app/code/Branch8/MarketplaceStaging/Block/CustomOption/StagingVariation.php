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
use Magento\CatalogStaging\Model\Product\Locator\StagingLocator;

class StagingVariation extends \Magento\Backend\Block\Template
{
    /**
     * @var \Webkul\OptionsWithStockAndImages\Model\VariationsFactory
     */
    protected $variationsFactory;

    /**
     * @var \Webkul\OptionsWithStockAndImages\Model\SwatchFactory
     */
    protected $swatchFactory;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlInterface;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManagerInterface;

    /**
     * @var \Webkul\OptionsWithStockAndImages\Helper\Data
     */
    protected $helper;

    /**
     * @var MarketplaceStagingHelper
     */
    protected $marketplaceStagingHelper;

    protected $tempData;

    protected $registry;

    /**
     * @var StagingLocator
     */
    protected $locator;

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
        StagingLocator $locator,
        array $data = []
    ) {

        $this->variationsFactory = $variationsFactory;
        $this->swatchFactory = $swatchFactory;
        $this->_urlInterface = $urlInterface;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->helper = $helper;
        $this->marketplaceStagingHelper = $marketplaceStagingHelper;
        $this->registry = $registry;
        $this->locator = $locator;
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
        return $this->locator->getProduct();
    }

    /**
     * Get saved product data
     *
     * @return string
     */
    public function getSavedData()
    {
        $this->tempData = $this->getTempData();
        if($this->tempData && array_key_exists('variation', $this->tempData)){
            return $this->tempData['variation'];
        }
        $product = $this->getProduct();
        if($product->getRowId()){
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
                }
                return $this->helper->jsonEncode($data);
            }
        }
        return '{}';
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
     * Get Swatch Data
     *
     * @return string
     */
    public function getSwatchData()
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
                return $this->helper->jsonEncode($collection->getData());
            }
        }
        return '{}';
    }

    public function getTempData(){
        $this->tempData = null;
        $productId = $this->getRequest()->getParam('id');
        if($productId){
            $productTemp = $this->marketplaceStagingHelper->getProductTempDataRepositoryByProductId($productId);
            if($productTemp){
                $productTemp = json_decode($productTemp->getInformation(), true);
                if(array_key_exists('options', $productTemp['product'])){
                    $options = $productTemp['product']['options'];
                    $optionIdsArr = [];
                    foreach ($options as $customOptionData) {
                        $optionIdsArr[] = $customOptionData['option_id'];
                    }

                    if(array_key_exists('wk_manage_variation', $productTemp['product'])){
                        $variationTemp = json_decode($productTemp['product']['wk_manage_variation'], true);
                        $this->tempData['variation'] = $this->buildVariationData($variationTemp, $productId);
                    }
                    if(array_key_exists('wk_manage_swatch', $productTemp['product'])){
                        $swatchTemp = json_decode($productTemp['product']['wk_manage_swatch'], true);
                        $this->tempData['swatch'] = $this->buildSwatchData($swatchTemp, $productId, $optionIdsArr);
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
        foreach ($swatchDataArr as $swatchData) {
            $var = ltrim($swatchData['name'], "wkswatch");
            $var = ltrim($var, "[");
            $var = rtrim($var, "]");
            $var = explode("][", $var);
            $wkswatch[$var[0]][$var[1]] = $swatchData['value'];
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
        foreach ($wkvariation as $variation) {
            $variationImage = [];
            if (!empty($variation['file'])) {
                foreach ($variation['file'] as $key => $file) {
                    $variationImage[$key] = rtrim($file, ".tmp");
                }
            }
            $variation['image'] = $variationImage;
            $variation['product_id'] = $productId;
            $data[] = $variation;
        }
        return json_encode($data);
    }
}
