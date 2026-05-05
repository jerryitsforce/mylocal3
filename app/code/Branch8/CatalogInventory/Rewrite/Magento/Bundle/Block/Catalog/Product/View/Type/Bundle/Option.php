<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\CatalogInventory\Rewrite\Magento\Bundle\Block\Catalog\Product\View\Type\Bundle;

class Option extends \Magento\Bundle\Block\Catalog\Product\View\Type\Bundle\Option
{
    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * Store pre-configured options
     *
     * @var int|array|string
     */
    protected $_selectedOptions;

    /**
     * Show if option has a single selection
     *
     * @var bool
     */
    protected $_showSingle;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $pricingHelper;

    /**
     * @var \Magento\Tax\Helper\Data
     */
    protected $_taxHelper;

    /**
     * @var \Magento\Catalog\Helper\Data
     */
    protected $_catalogHelper;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Catalog\Helper\Data $catalogData
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Framework\Math\Random $mathRandom
     * @param \Magento\Checkout\Helper\Cart $cartHelper
     * @param \Magento\Tax\Helper\Data $taxData
     * @param \Magento\Framework\Pricing\Helper\Data $pricingHelper
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Catalog\Helper\Data $catalogData,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Framework\Math\Random $mathRandom,
        \Magento\Checkout\Helper\Cart $cartHelper,
        \Magento\Tax\Helper\Data $taxData,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        array $data = []
    ) {
        $this->pricingHelper = $pricingHelper;
        $this->_catalogHelper = $catalogData;
        $this->_taxHelper = $taxData;
        $this->stockRegistry = $stockRegistry;
        parent::__construct(
            $context,
            $jsonEncoder,
            $catalogData,
            $registry,
            $string,
            $mathRandom,
            $cartHelper,
            $taxData,
            $pricingHelper,
            $data
        );
    }

    /**
     * Retrieve current product stock qty
     *
     * @return float
     */
    public function getStockQty($product)
    {
        $qty = 0;
        $productId = $product->getId();
        if ($productId) {
            $qty = $this->getProductStockQty($product);
        }
        return $qty;
    }

    /**
     * Retrieve product stock qty
     *
     * @param Product $product
     * @return float
     */
    public function getProductStockQty($product)
    {
        return $this->stockRegistry->getStockStatus($product->getId(), $product->getStore()->getWebsiteId())->getQty();
    }

    /**
     * Retrieve threshold of qty to display stock qty message
     *
     * @return string
     */
    public function getThresholdQty()
    {
        $qty = (float)$this->_scopeConfig->getValue(
            \Magento\CatalogInventory\Block\Stockqty\AbstractStockqty::XML_PATH_STOCK_THRESHOLD_QTY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return $qty;
    }

    /**
     * Retrieve visibility of stock qty message
     *
     * @return bool
     */
    public function isMsgVisible($product)
    {
        return $this->getStockQty($product) > 0
            && $this->getStockQtyLeft($product) > 0
            && $this->getStockQtyLeft($product) < $this->getThresholdQty();
    }

    /**
     * Retrieve current product qty left in stock
     *
     * @return float
     */
    public function getStockQtyLeft($product)
    {
        $stockItem = $this->stockRegistry->getStockItem($product->getId());
        $minStockQty = $stockItem->getMinQty();
        return $this->getStockQty($product) - $minStockQty;
    }
    
    /**
     * Check if option has a single selection
     *
     * @return bool
     */
    public function showSingle()
    {
        if ($this->_showSingle === null) {
            $option = $this->getOption();
            $selections = $option->getSelections();

            $this->_showSingle = count($selections) == 1 && $option->getRequired();
        }

        return $this->_showSingle;
    }

    /**
     * Retrieve default values for template
     *
     * @return array
     */
    public function getDefaultValues()
    {
        $option = $this->getOption();
        $default = $option->getDefaultSelection();
        $selections = $option->getSelections();
        $selectedOptions = $this->_getSelectedOptions();
        $inPreConfigured = $this->getProduct()->hasPreconfiguredValues() &&
            $this->getProduct()->getPreconfiguredValues()->getData('bundle_option_qty/' . $option->getId());

        if (empty($selectedOptions) && $default) {
            $defaultQty = $default->getSelectionQty() * 1;
            $canChangeQty = $default->getSelectionCanChangeQty();
        } elseif (!$inPreConfigured && $selectedOptions && is_numeric($selectedOptions)) {
            $selectedSelection = $option->getSelectionById($selectedOptions);
            $defaultQty = $selectedSelection->getSelectionQty() * 1;
            $canChangeQty = $selectedSelection->getSelectionCanChangeQty();
        } elseif (!$this->showSingle() || $inPreConfigured) {
            $defaultQty = $this->_getSelectedQty();
            $canChangeQty = (bool)$defaultQty;
        } else {
            $defaultQty = $selections[0]->getSelectionQty() * 1;
            $canChangeQty = $selections[0]->getSelectionCanChangeQty();
        }

        return [$defaultQty, $canChangeQty];
    }

    /**
     * Collect selected options
     *
     * @return int|array|string
     */
    protected function _getSelectedOptions()
    {
        if ($this->_selectedOptions === null) {
            $this->_selectedOptions = [];

            /** @var \Magento\Bundle\Model\Option $option */
            $option = $this->getOption();

            if ($this->getProduct()->hasPreconfiguredValues()) {
                $selectionId = $this->getProduct()->getPreconfiguredValues()->getData(
                    'bundle_option/' . $option->getId()
                );
                $this->assignSelection($option, $selectionId);
            }
        }

        return $this->_selectedOptions;
    }

    /**
     * Set selected options.
     *
     * @param \Magento\Bundle\Model\Option $option
     * @param mixed $selectionId
     * @return void
     * @since 100.2.0
     */
    protected function assignSelection(\Magento\Bundle\Model\Option $option, $selectionId)
    {
        if (is_array($selectionId)) {
            $this->_selectedOptions = $selectionId;
        } elseif ($selectionId && $option->getSelectionById($selectionId)) {
            $this->_selectedOptions = $selectionId;
        } elseif (!$option->getRequired()) {
            $this->_selectedOptions = 'None';
        }
    }

    /**
     * Define if selection is selected
     *
     * @param  Product $selection
     * @return bool
     */
    public function isSelected($selection)
    {
        $selectedOptions = $this->_getSelectedOptions();
        if (is_numeric($selectedOptions)) {
            return $selection->getSelectionId() == $selectedOptions;
        } elseif (is_array($selectedOptions) && !empty($selectedOptions)) {
            return in_array($selection->getSelectionId(), $selectedOptions);
        } elseif ($selectedOptions == 'None') {
            return false;
        }
        return $selection->getIsDefault() && $selection->isSaleable();
    }

    /**
     * Retrieve selected option qty
     *
     * @return int
     */
    protected function _getSelectedQty()
    {
        if ($this->getProduct()->hasPreconfiguredValues()) {
            $selectedQty = (double)$this->getProduct()->getPreconfiguredValues()->getData(
                'bundle_option_qty/' . $this->getOption()->getId()
            );
            if ($selectedQty < 0) {
                $selectedQty = 0;
            }
        } else {
            $selectedQty = 0;
        }

        return $selectedQty;
    }

    /**
     * Get product model
     *
     * @return Product
     */
    public function getProduct()
    {
        if (!$this->hasData('product')) {
            $this->setData('product', $this->_coreRegistry->registry('current_product'));
        }
        return $this->getData('product');
    }

    /**
     * Get bundle option price title.
     *
     * @param Product $selection
     * @param bool $includeContainer
     * @return string
     */
    public function getSelectionQtyTitlePrice($selection, $includeContainer = true)
    {
        $this->setFormatProduct($selection);
        $priceTitle = '<span class="product-name">'
            . $selection->getSelectionQty() * 1
            . ' x '
            . $this->escapeHtml($selection->getName())
            . '</span>';

        $priceTitle .= ' &nbsp; ' . ($includeContainer ? '<span class="price-notice">' : '') . '+' .
            $this->renderPriceString($selection, $includeContainer) . ($includeContainer ? '</span>' : '');

        return $priceTitle;
    }

    /**
     * Get price for selection product
     *
     * @param Product $selection
     * @return int|float
     */
    public function getSelectionPrice($selection)
    {
        $price = 0;
        $store = $this->getProduct()->getStore();
        if ($selection) {
            $price = $this->getProduct()->getPriceModel()->getSelectionPreFinalPrice(
                $this->getProduct(),
                $selection,
                1
            );
            if (is_numeric($price)) {
                $price = $this->pricingHelper->currencyByStore($price, $store, false);
            }
        }
        return is_numeric($price) ? $price : 0;
    }

    /**
     * Get title price for selection product
     *
     * @param Product $selection
     * @param bool $includeContainer
     * @return string
     */
    public function getSelectionTitlePrice($selection, $includeContainer = true)
    {
        $priceTitle = '<span class="product-name">' . $this->escapeHtml($selection->getName()) . '</span>';
        $priceTitle .= ' &nbsp; ' . ($includeContainer ? '<span class="price-notice">' : '') . '+'
            . $this->renderPriceString($selection, $includeContainer) . ($includeContainer ? '</span>' : '');
        return $priceTitle;
    }

    /**
     * Set JS validation container for element
     *
     * @param int $elementId
     * @param int $containerId
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setValidationContainer($elementId, $containerId)
    {
        return '';
    }

    /**
     * Clear selected option when setting new option
     *
     * @param \Magento\Bundle\Model\Option $option
     * @return mixed
     */
    public function setOption(\Magento\Bundle\Model\Option $option)
    {
        $this->_selectedOptions = null;
        $this->_showSingle = null;
        return parent::setOption($option);
    }

    /**
     * Format price string
     *
     * @param Product $selection
     * @param bool $includeContainer
     * @return string
     */
    public function renderPriceString($selection, $includeContainer = true)
    {
        /** @var \Magento\Bundle\Pricing\Price\BundleOptionPrice $price */
        $price = $this->getProduct()->getPriceInfo()->getPrice('bundle_option');
        $amount = $price->getOptionSelectionAmount($selection);

        $priceHtml = $this->getLayout()->getBlock('product.price.render.default')->renderAmount(
            $amount,
            $price,
            $selection,
            [
                'include_container' => $includeContainer
            ]
        );

        return $priceHtml;
    }
}