<?php

namespace Branch8\MarketplaceStaging\Block\CustomOption;

use Magento\Catalog\Model\Product;
use Magento\Framework\View\Element\Template;

class Options extends Template
{
    /**
     * @var string
     */
    protected $_template = 'Branch8_MarketplaceStaging::catalog/product/edit/options.phtml';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var Product
     */
    protected $_product;

    /**
     * @var Product
     */
    protected $_productInstance;

    /**
     * Option constructor.
     * @param Template\Context $context
     * @param Product $product
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Product $product,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_product = $product;
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * @return Template
     */
    protected function _prepareLayout()
    {
        $this->addChild(
            'add_button',
            \Branch8\MarketplaceStaging\Block\Widget\Button::class,
            ['label' => __('Add New Option'), 'class' => 'add', 'id' => 'add_new_defined_option']
        );

        $this->addChild(
            'add_wkvariations_button',
            \Branch8\MarketplaceStaging\Block\Widget\Button::class,
            ['label' => __('Manage Variations'), 'class' => 'add', 'id' => 'add_wkvariations_button', 'data-index' => 'wkvariations']
        );

        $this->addChild('options_box', Options\Option::class);

        return parent::_prepareLayout();
    }

    /**
     * @return string
     */
    public function getAddWKButtonHtml()
    {
        return $this->getChildHtml('add_wkvariations_button');
    }

    /**
     * @return string
     */
    public function getAddButtonHtml()
    {
        return $this->getChildHtml('add_button');
    }

    /**
     * Default will call html from this function
     * @return string
     */
    public function getOptionsBoxHtml()
    {
        return $this->getChildHtml('options_box');
    }

    /**
     * Get Product
     *
     * @return Product
     */
    public function getProduct()
    {
        if (!$this->_productInstance) {
            $product = $this->_coreRegistry->registry('product');
            if ($product) {
                $this->_productInstance = $product;
            } else {
                $this->_productInstance = $this->_product;
            }
        }

        return $this->_productInstance;
    }
    
    public function isBought(){
        $options = $this->getProduct()->getOptions();
        if ($options) {
            foreach ($options as $option) {
                if ($option->getGroupByType() == 'select') {
                    if ($option->getValues()) {
                        foreach ($option->getValues() as $value) {
                            if (isset($value['is_bought']) && $value['is_bought'] == 1) {
                                return true;
                            }
                        }
                    }
                }
            }
        }
        return false;
    }

    /**
     * Retreive Product Type
     *
     * @return string
     */
    public function getProductType()
    {
        $productId = $this->getRequest()->getParam('id');
        if ($productId) {
            $product = $this->getProduct();
            $productType = $product->getTypeId();
        } else {
            $productType = $this->getRequest()->getParam('type');
        }
        return $productType;
    }
}
