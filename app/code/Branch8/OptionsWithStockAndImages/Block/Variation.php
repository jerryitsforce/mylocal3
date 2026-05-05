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

namespace Branch8\OptionsWithStockAndImages\Block;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\Downloadable\Model\Product\Type as Downloadable;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Catalog\Model\Product\Image\UrlBuilder as ImageUrlBuilder;

class Variation extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $_imageHelper;

    /**
     * @var \Webkul\OptionsWithStockAndImages\Model\VariationsFactory
     */
    protected $variationsFactory;

    /**
     * @var \Webkul\OptionsWithStockAndImages\Model\SwatchFactory
     */
    protected $swatchFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManagerInterface;

    /**
     * @var \Webkul\OptionsWithStockAndImages\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrencyObject;

    /**
     * @var \Branch8\OptionsWithStockAndImages\Api\PriceManagementInterface
     */
    protected $priceManagement;

    protected ImageUrlBuilder $imageUrlBuilder;

    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Webkul\OptionsWithStockAndImages\Model\VariationsFactory $variationsFactory
     * @param \Webkul\OptionsWithStockAndImages\Model\SwatchFactory $swatchFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Webkul\OptionsWithStockAndImages\Helper\Data $helper
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param PriceCurrencyInterface $priceCurrencyObject
     * @param ImageUrlBuilder $imageUrlBuilder
     * @param \Branch8\OptionsWithStockAndImages\Api\PriceManagementInterface $priceManagement
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Webkul\OptionsWithStockAndImages\Model\VariationsFactory $variationsFactory,
        \Webkul\OptionsWithStockAndImages\Model\SwatchFactory $swatchFactory,
        \Magento\Framework\Registry $registry,
        \Webkul\OptionsWithStockAndImages\Helper\Data $helper,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        PriceCurrencyInterface $priceCurrencyObject,
        ImageUrlBuilder $imageUrlBuilder,
        \Branch8\OptionsWithStockAndImages\Api\PriceManagementInterface $priceManagement,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->variationsFactory = $variationsFactory;
        $this->swatchFactory = $swatchFactory;
        $this->registry = $registry;
        $this->_imageHelper = $context->getImageHelper();
        $this->storeManagerInterface = $storeManagerInterface;
        $this->priceCurrencyObject = $priceCurrencyObject;
        $this->imageUrlBuilder = $imageUrlBuilder;
        $this->priceManagement = $priceManagement;
        parent::__construct($context, $data);
    }

    /**
     * Check isOWSIProduct or not
     *
     * @return int
     */
    public function isOWSIProduct()
    {
        $productId = $this->getCurrentProduct()->getRowId();
        $count = 0;
        if ($productId) {
            $collection = $this->variationsFactory->create()
                            ->getCollection()
                            ->addFieldToFilter("product_id", $productId);
            $count = $collection->getSize();
        }
        return $count;
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

    /**
     * Get Saved Data for a product
     *
     * @return string
     */
    public function getSavedData()
    {
        $productId = $this->getCurrentProduct()->getRowId();
        if ($productId) {
            $collection = $this->variationsFactory->create()
                                                ->getCollection()
                                                ->addFieldToFilter("product_id", $productId);
            if ($collection->getSize()) {
                $data = $collection->getData();
                $count = count($data);
                for ($i=0; $i < $count; $i++) {
                    $data[$i]['image'] = explode(',', $data[$i]['image']);
                }
                return $this->helper->jsonEncode($data);
            }
        }
        return '{}';
    }

    /**
     * Get path of uploaded media
     *
     * @return string
     */
    public function getMediaUrl()
    {
        return $this->storeManagerInterface->getStore()
        ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).'wkosi/products';
    }

    /**
     * Get Current Product
     *
     * @return \Magento\Framework\Registry
     */
    public function getCurrentProduct()
    {
        return $this->registry->registry('current_product');
    }

    /**
     * Get isConfigurableProduct
     *
     * @return int
     */
    public function isConfigurableProduct()
    {
        $result = 0;
        if ($this->getCurrentProduct()->getTypeId() == Configurable::TYPE_CODE) {
            $result = 1;
        }
        return $result;
    }

    /**
     * Get isSimpleProduct
     *
     * @return int
     */
    public function isSimpleProduct()
    {
        $result = 0;
        if ($this->getCurrentProduct()->getTypeId() == Type::TYPE_SIMPLE) {
            $result = 1;
        }
        return $result;
    }

    /**
     * Get Options
     *
     * @return string
     */
    public function getOptions()
    {
        $result = $this->getOptionData($this->getCurrentProduct());
        return $this->helper->jsonEncode($result);
    }

    /**
     * Get all dropdown and radio option of a product
     *
     * @param int $productId
     * @return array
     */
    public function getOptionData($product)
    {
        $optionData = [];
        foreach ($product->getOptions() as $option) {
            $optType = $option->getType();
            if ($optType == "drop-down" || $optType == "drop_down" || $optType == "radio") {
                $optionId = $option->getId();
                $optionData[$optionId] = [];
                foreach ($option->getValues() as $value) {
                    if(!$value->getIsVisible()){
                        continue;
                    }
                    $valueId = $value->getId();
                    $optionData[$optionId][$valueId] = $value->getDefaultTitle() ?? $value->getTitle();
                }
            }
        }
        return $optionData;
    }

     /**
      * Get Variations By Option Val
      *
      * @param string $title
      * @return array
      */
    public function getVariationsByOptionVal($title)
    {
        $productId = $this->getCurrentProduct()->getRowId();
        $collection = $this->variationsFactory->create()
                                            ->getCollection()
                                            ->addFieldToFilter("product_id", $productId);
        $data = $collection->getVariationsIdsByOptionValue($title);
        $data_return = [];
        foreach($data as $val){
            if($val['product_id'] == $productId){
                $data_return[] = $val;
            }
        }
        return $data_return;
    }

    /**
     * Get Option Val Price
     *
     * @param string $value
     * @return string
     */
    public function getOptionValPrice($value)
    {
        $pricingValue = $value->getPrice($value->getPriceType() == 'percent');
        if ($pricingValue == 0) {
            return 0;
        }

        $sign = '+';
        if ($pricingValue < 0) {
            $sign = '-';
            $pricingValue = 0 - $pricingValue;
        }
        $priceStr = $sign;
        return $priceStr.$pricingValue;
    }

    /**
     * Get all dropdown and radio option of a product
     *
     * @param int $productId
     * @return array
     */
    public function getProductOptions($product)
    {
        $optionData = [];
        foreach ($product->getOptions() as $option) {
            $type = $option->getType();
            if ($type == "drop-down" || $type == "drop_down" || $type == "radio") {
                $optionData[] = $option;
            }
        }

        return $optionData;
    }

    /**
     * Get Options Json Config
     *
     * @return string
     */
    public function getOptionsJsonConfig()
    {
        $product = $this->getCurrentProduct();
        $productRowId = $product->getRowId();
        if ($product->getPriceInfo()->getPrice('final_price')->getValue()) {
            $productPrice = $product->getPriceInfo()->getPrice('final_price')->getValue();
        } else {
            $productPrice = $product->getPrice();
        }
        if ($product->getPriceInfo()->getPrice('regular_price')->getValue()) {
            $oldPrice = $product->getPriceInfo()->getPrice('regular_price')->getValue();
        } else {
            $oldPrice = $product->getPrice();
        }

        // Fetch calculated variation prices
        $calculatedPrices = $this->priceManagement->getFinalPrice($product->getId());
        $priceMap = [];
        if (is_array($calculatedPrices)) {
            foreach ($calculatedPrices as $cp) {
                $priceMap[$cp['variation_comb']] = $cp['final_price'];
            }
        }

        $options =  $this->getProductOptions($product);
        $data = [];
        $data['attributes'] = [];
        $data['template'] = '$<%- data.price %>';
        $data['currencyFormat'] = '$%s';
        $data['optionPrices'] = [];
        $data['priceFormat'] = [
            'pattern' => '$%s',
            'precision' => '2',
            'requiredPrecision' => '2',
            'decimalSymbol' => '.',
            'groupSymbol' => ',',
            'groupLength' => '3',
            'integerRequired' => ''
        ];
        $data['prices'] = [
            'oldPrice' => [
                'amount' => $oldPrice
            ],
            'basePrice' => [
                'amount' => $productPrice
            ],
            'finalPrice' => [
                'amount' => $productPrice
            ]
        ];
        $data['productId'] = $product->getId();
        $data['chooseText'] = __('Choose an Option');
        $data['images'] = [];
        foreach ($options as $option) {
            $optType = $option->getType();
            if ($optType == "drop-down" || $optType == "drop_down" || $optType == "radio") {
                $optId = $option->getId();
                $data['attributes'][$optId] = [];
                $data['attributes'][$optId]['id'] = $optId;
                $data['attributes'][$optId]['code'] = $option->getDefaultTitle() ?? $option->getTitle();
                $data['attributes'][$optId]['label'] = $option->getDefaultTitle() ?? $option->getTitle();
                $data['attributes'][$optId]['title'] = $option->getDefaultTitle() ?? $option->getTitle();
                $data['attributes'][$optId]['options'] = [];
                $i = 0;
                foreach ($option->getValues() as $value) {
                    if(!$value->getIsVisible()){
                        continue;
                    }
                    $valId = $value->getId() ?? $value->getValueId();
                    $data['attributes'][$optId]['options'][$i]['id'] = $valId;
                    $data['attributes'][$optId]['options'][$i]['label'] = $value->getDefaultTitle() ?? $value->getTitle();
                    $valuePrice = $value->getPrice();
                    $valuePrice = $this->convertPrice($valuePrice);
                    if ($value->getPriceType() == 'percent') {
                        $valuePrice = $this->getOptionValPrice($value);
                    }
                    $data['attributes'][$optId][$valId] = [
                        'prices' => [
                            'oldPrice' => [
                                'amount' => $valuePrice,
                                'adjustments' => []
                            ],
                            'basePrice' => [
                                'amount' => $valuePrice
                            ],
                            'finalPrice' => [
                                'amount' => $valuePrice
                            ]
                        ]
                    ];
                    $title = strtolower((string)($value->getDefaultTitle() ?? $value->getTitle()));
                    $variationsData = $this->getVariationsByOptionVal($title);
                    foreach ($variationsData as $key => $variation) {
                        $variationId = $variation['entity_id'];
                        $variationImages = $variation['image'];
                        $price = $variation['price'];
                        $comb = $variation['comb']; // Assuming 'comb' is available in variation data

                        // Use calculated price if available
                        $finalPrice = isset($priceMap[$comb]) ? $priceMap[$comb] : $price;

                        $data['attributes'][$optId]['options'][$i]['products'][$key] = $variationId;
                        if (empty($data['optionPrices'][$variationId]['oldPrice'])) {
                            $data['optionPrices'][$variationId] = [
                                'oldPrice' => [
                                    'amount' => $oldPrice
                                ],
                                'basePrice' => [
                                    'amount' => $price
                                ],
                                'finalPrice' => [
                                    'amount' => $finalPrice
                                ],
                                'tierPrices' => [],
                                'msrpPrice' => [
                                    'amount' => ''
                                ]
                            ];
                        }
                        $data['optionPrices'][$variationId]['oldPrice']['amount'] += $valuePrice;
                        $data['optionPrices'][$variationId]['basePrice']['amount']+= $valuePrice;
                        $data['optionPrices'][$variationId]['finalPrice']['amount'] += $valuePrice;
                        $data['images'][$variationId] = explode(',', $variationImages);
                        $data['optionIds'][$variationId]['optionId'] = $valId;
                        $data['index'][$variationId][$optId] = $valId;
                    }
                    $i++;
                }
            }
        }

        $defaultMainImages = [];
//        $productImages = $product->getMediaGalleryEntries();
        $productImages = $product->getMediaGalleryImages()->getItems();
        foreach($productImages as $productImage){
            $defaultMainImage = [];
            $productImage = $productImage->getFile();

            $thumbImage = $this->imageUrlBuilder
                ->getUrl($productImage,  'product_page_image_small');
            $baseImage = $this->imageUrlBuilder
                ->getUrl($productImage,  'product_page_image_medium');
            $fullImage = $this->imageUrlBuilder
                ->getUrl($productImage,  'product_page_image_large');
            $defaultMainImage['thumb'] = $thumbImage;
            $defaultMainImage['img'] = $baseImage;
            $defaultMainImage['full'] = $fullImage;
            $defaultMainImage['caption'] = '';
            $defaultMainImage['position'] = 1;
            $defaultMainImage['isMain'] = 1;
            $defaultMainImage['isSubImage'] = 0;
            $defaultMainImage['type'] = 'image';
            $defaultMainImage['videoUrl'] = '';
            $defaultMainImages[] = $defaultMainImage;
        }

        $variationImgs = [];
        foreach ($data['images'] as $variationId => $variationImages) {
            $data['images'][$variationId] = $defaultMainImages;
            $i = 0;
            foreach ($variationImages as $key => $variationImage) {
                // $data['images'][$variationId][$key] = $defaultMainImages;
                $variationImg = [];
                if (!empty($variationImage)) {
                    $image = $this->getMediaUrl().$variationImage;
                    $variationImg['thumb'] = $image;
                    $variationImg['img'] = $image;
                    $variationImg['full'] = $image;
                    $variationImg['caption'] = '';
                    $variationImg['position'] = 1;
                    $variationImg['isMain'] = 1;
                    $variationImg['isVariantStartImage'] = $i === 0 ? 1 : 0;
                    $variationImg['optionId'] = $data['optionIds'][$variationId]['optionId'];
                    $variationImg['type'] = 'image';
                    $variationImg['videoUrl'] = '';
                    $i++;
                }
                if(!empty($variationImg)){
                    $variationImgs[] = $variationImg;
                }
            }
        }

        foreach ($data['images'] as $variationId => $variationImages) {
            $data['images'][$variationId] = array_merge($defaultMainImages, $variationImgs);
        }

        return $this->helper->jsonEncode($data);
    }

     /**
      * Get Options Json Swatch Config
      *
      * @return string
      */
    public function getOptionsJsonSwatchConfig()
    {
        $product = $this->getCurrentProduct();
        $options =  $this->getProductOptions($product);
        $data = [];
        foreach ($options as $option) {
            $optType = $option->getType();
            $optionDefaultTittle = strtolower((string)($option->getDefaultTitle() ?? $option->getTitle()));
            if ($optType == "drop-down" || $optType == "drop_down" || $optType == "radio") {
                $optId = $option->getId();
                $data[$optId] = [];
                foreach ($option->getValues() as $value) {
                    if(!$value->getIsVisible()){
                        continue;
                    }
                    $valId = $value->getId() ?? $value->getValueId();
                    $data[$optId][$valId] = [
                        'type' => '0',
                        'value' => $value->getDefaultTitle() ?? $value->getTitle(),
                        'label'  => $value->getDefaultTitle() ?? $value->getTitle(),
                        'default_tittle' => $optionDefaultTittle
                    ];
                    $data[$optId]['additional_data'] = '{
                        "swatch_input_type":"text",
                        "update_product_preview_image":1,
                        "use_product_image_for_swatch":0
                    }';
                }
            }
        }
        return $this->helper->jsonEncode($data);
    }

     /**
      * Get Options Media Callback
      *
      * @return string
      */
    public function getOptionsMediaCallback()
    {
        return '';
    }

    /**
     * Get Options Var
     *
     * @return string
     */
    public function getOptionsVar()
    {
        return '';
    }

     /**
      * Get Options Json Swatch Size Config
      *
      * @return string
      */
    public function getOptionsJsonSwatchSizeConfig()
    {
        $data = [
            'swatchImage' => [
                'width' => '30',
                'height' => '20'
            ],
            'swatchThumb' => [
                'height' => '90',
                'width' => '110'
            ]
        ];
        return $this->helper->jsonEncode($data);
    }

    /**
     * Get isBundleProduct
     *
     * @return int
     */
    public function isBundleProduct()
    {
        $result = 0;
        if ($this->getCurrentProduct()->getTypeId() == Type::TYPE_BUNDLE) {
            $result = 1;
        }
        return $result;
    }

     /**
      * Get isDownloadableProduct
      *
      * @return int
      */
    public function isDownloadableProduct()
    {
        $result = 0;
        if ($this->getCurrentProduct()->getTypeId() == Downloadable::TYPE_DOWNLOADABLE) {
            $result = 1;
        }
        return $result;
    }

    /**
     * Get Converted Currency
     *
     * @param int $amount
     * @param int $store
     * @param float $currency
     *
     * @return float
     */
    public function convertPrice($amount = 0, $store = null, $currency = null)
    {
        if ($store == null) {
            $store = $this->storeManagerInterface->getStore()->getStoreId();
        }
        $amount = $this->priceCurrencyObject->convert($amount, $store, $currency);
        return $amount;
    }
}
