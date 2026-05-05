<?php

namespace Branch8\MarketplaceStaging\Block\CustomOption\Options;

use Magento\CatalogStaging\Model\Product\Locator\StagingLocator;
use Magento\Framework\View\Element\Template;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Api\Data\ProductCustomOptionInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class StagingOption extends Template
{
    /**
     * @var \Magento\Framework\DataObject[]
     */
    protected $_values;

    /**
     * @var int
     */
    protected $_itemCount = 1;

    /**
     * @var string
     */
    protected $_template = 'Branch8_MarketplaceStaging::catalog/product/edit/options/staging_option.phtml';

    /**
     * @var \Magento\Catalog\Model\ProductOptions\ConfigInterface
     */
    protected $_productOptionConfig;

    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    protected $dataObjectFactory;

    /**
     * @var \Magento\Config\Model\Config\Source\Yesno
     */
    protected $_configYesNo;

    /**
     * @var \Magento\Catalog\Model\Config\Source\Product\Options\Type
     */
    protected $_optionType;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $json;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var StagingLocator
     */
    protected $locator;

    /**
     * @var Product
     */
    protected $_productInstance;

    /**
     * Option constructor.
     * @param Template\Context $context
     * @param \Magento\Config\Model\Config\Source\Yesno $configYesNo
     * @param \Magento\Catalog\Model\Config\Source\Product\Options\Type $optionType
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     * @param \Magento\Catalog\Model\ProductOptions\ConfigInterface $productOptionConfig
     * @param \Magento\Framework\Serialize\Serializer\Json $json
     * @param StagingLocator $locator
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Magento\Config\Model\Config\Source\Yesno $configYesNo,
        \Magento\Catalog\Model\Config\Source\Product\Options\Type $optionType,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Magento\Catalog\Model\ProductOptions\ConfigInterface $productOptionConfig,
        \Magento\Framework\Serialize\Serializer\Json $json,
        StagingLocator $locator,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_optionType = $optionType;
        $this->_configYesNo = $configYesNo;
        $this->_productOptionConfig = $productOptionConfig;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->json = $json;
        $this->locator = $locator;
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Class constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('Branch8_MarketplaceStaging::catalog/product/edit/options/staging_option.phtml');

        $this->setCanReadPrice(true);
        $this->setCanEditPrice(true);
    }

    /**
     * @return int
     */
    public function getItemCount()
    {
        return $this->_itemCount;
    }

    /**
     * @param int $itemCount
     * @return $this
     */
    public function setItemCount($itemCount)
    {
        $this->_itemCount = max($this->_itemCount, $itemCount);
        return $this;
    }

    /**
     * Retrieve options field name prefix
     *
     * @return string
     */
    public function getFieldName()
    {
        return 'product[options]';
    }

    /**
     * Retrieve options field id prefix
     *
     * @return string
     */
    public function getFieldId()
    {
        return 'staging_product_option';
    }

    /**
     * Check block is readonly
     *
     * @return bool
     */
    public function isReadonly()
    {
        return false;
    }

    /**
     * @return $this
     * @codingStandardsIgnoreStart
     */
    protected function _prepareLayout()
    {
        foreach ($this->_productOptionConfig->getAll() as $option) {
            $this->addChild(
                $option['name'] . '_option_type',
                str_replace(
                    "Magento\\Catalog\\Block\\Adminhtml\\Product",
                    "Branch8\\MarketplaceStaging\\Block\\CustomOption\\Template",
                    $option['renderer']
                )
            );
        }
        // @codingStandardsIgnoreEnd
        return parent::_prepareLayout();
    }

    /**
     * Default will call html from this function
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAddButtonId()
    {
        return $this->getLayout()->getBlock('admin.product.options')->getChildBlock('add_button')->getId();
    }

    /**
     * @return string
     */
    public function getTypeSelectHtml()
    {
        $optionTypeArray = $this->_optionType->toOptionArray();
        $fieldId = $this->getFieldId();
        $select = $this->getLayout()->createBlock(
            \Magento\Framework\View\Element\Html\Select::class
        )->setData(
            [
                'id' => $fieldId . '_<%- data.id %>_type',
                'class' => 'select select-product-option-type required-option-select',
            ]
        )->setName(
            $this->getFieldName() . '[<%- data.id %>][type]'
        )->setOptions(
            $optionTypeArray
        );

        return $select->getHtml();
    }

    /**
     * @return string
     */
    public function getRequireSelectHtml()
    {
        $select = $this->getLayout()->createBlock(
            \Magento\Framework\View\Element\Html\Select::class
        )->setData(
            ['id' => $this->getFieldId() . '_<%- data.id %>_is_require', 'class' => 'select']
        )->setName(
            $this->getFieldName() . '[<%- data.id %>][is_require]'
        )->setOptions(
            $this->_configYesNo->toOptionArray()
        );

        return $select->getHtml();
    }

    /**
     * Retrieve html templates for different types of product custom options
     *
     * @return string
     */
    public function getTemplatesHtml()
    {
        $canEditPrice = $this->getCanEditPrice();
        $canReadPrice = $this->getCanReadPrice();
        if($this->getChildBlock('select_option_type')){
            $this->getChildBlock('select_option_type')->setCanReadPrice($canReadPrice)->setCanEditPrice($canEditPrice);
        }
        if($this->getChildBlock('file_option_type')){
            $this->getChildBlock('file_option_type')->setCanReadPrice($canReadPrice)->setCanEditPrice($canEditPrice);
        }
        if($this->getChildBlock('date_option_type')){
            $this->getChildBlock('date_option_type')->setCanReadPrice($canReadPrice)->setCanEditPrice($canEditPrice);
        }
        if($this->getChildBlock('text_option_type')){
            $this->getChildBlock('text_option_type')->setCanReadPrice($canReadPrice)->setCanEditPrice($canEditPrice);
        }

        $templates = $this->getChildHtml(
            'text_option_type'
        ) . "\n" . $this->getChildHtml(
            'file_option_type'
        ) . "\n" . $this->getChildHtml(
            'select_option_type'
        ) . "\n" . $this->getChildHtml(
            'date_option_type'
        );

        return $templates;
    }

    /**
     * @return \Magento\Framework\DataObject[]
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getOptionValues()
    {
        $optionsArr = $this->getProduct()->getOptions();
        if ($optionsArr == null) {
            $optionsArr = [];
        }
        if (!$this->_values || $this->getIgnoreCaching()) {
            $showPrice = $this->getCanReadPrice();
            $values = [];
            foreach ($optionsArr as $option) {
                /* @var $option \Magento\Catalog\Model\Product\Option */
                $this->setItemCount($option->getOptionId());

                $value = [];

                $value['id'] = $option->getOptionId();
                $value['item_count'] = $this->getItemCount();
                $value['option_id'] = $option->getOptionId();
                $value['title'] = $option->getTitle();
                $value['type'] = $option->getType();
                $value['is_require'] = $option->getIsRequire();
                $value['sort_order'] = $option->getSortOrder();
                $value['show_delete_button'] = 1;

                if ($option->getGroupByType() == ProductCustomOptionInterface::OPTION_GROUP_SELECT) {
                    $i = 0;
                    $itemCount = 0;
                    if(is_array($option->getValues()) || is_object($option->getValues())) {
                        foreach ($option->getValues() as $_value) {
                            /* @var $_value \Magento\Catalog\Model\Product\Option\Value */
                            if ($_value->getIsBought() == 1) {
                                $value['show_delete_button'] = 0;
                            }
                            $value['optionValues'][$i] = [
                                'item_count' => max($itemCount, $_value->getOptionTypeId()),
                                'option_id' => $_value->getOptionId(),
                                'option_type_id' => $_value->getOptionTypeId(),
                                'title' => $_value->getTitle(),
                                'price' => $showPrice ? $this->getPriceValue(
                                    $_value->getPrice(),
                                    $_value->getPriceType()
                                ) : '',
                                'price_type' => $showPrice ? $_value->getPriceType() : 0,
                                'sku' => $_value->getSku(),
                                'is_bought' => $_value->getIsBought(),
                                'is_visible' => $_value->getIsVisible(),
                                'sort_order' => $_value->getSortOrder(),
                                'title_option' => $_value->getTitle(),
                            ];
                            $i++;
                        }
                    }
                } else {
                    $value['price'] = $showPrice ? $this->getPriceValue(
                        $option->getPrice(),
                        $option->getPriceType()
                    ) : '';
                    $value['price_type'] = $option->getPriceType();
                    $value['sku'] = $option->getSku();
                    $value['max_characters'] = $option->getMaxCharacters();
                    $value['file_extension'] = $option->getFileExtension();
                    $value['image_size_x'] = $option->getImageSizeX();
                    $value['image_size_y'] = $option->getImageSizeY();
                }
                $values[] = new \Magento\Framework\DataObject($value);
            }
            $this->_values = $values;
        }

        return $this->_values;
    }

    /**
     * @param float $value
     * @param string $type
     * @return string
     */
    public function getPriceValue($value, $type)
    {
        if (!$value) {
            return '';
        }

        if ($type == 'percent' || $type == 'fixed' || $type == 'abs') {
            return number_format(round((float)$value), 0, null, '');
        }
        return '';
    }

    /**
     * @param mixed $data
     * @return \Magento\Framework\Serialize\Serializer\Json
     */
    public function serializeJson($data)
    {
        return $this->json->serialize($data);
    }

    /**
     * Get Product
     *
     * @return Product
     */
    public function getProduct()
    {
        return $this->locator->getProduct();
    }

    /**
     * Set Product
     *
     * @param Product $product
     * @return $this
     */
    public function setProduct($product)
    {
        $this->_productInstance = $product;
        return $this;
    }
}
