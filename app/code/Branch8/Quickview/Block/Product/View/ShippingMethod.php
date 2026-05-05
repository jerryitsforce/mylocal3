<?php

namespace Branch8\Quickview\Block\Product\View;

use Magento\Catalog\Api\ProductRepositoryInterface;

class ShippingMethod extends \Magento\Framework\View\Element\Template{

    protected $productRepository;

    protected $_coreRegistry;

    protected $hotaiShippingHelper;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        ProductRepositoryInterface $productRepository,
        \Magento\Framework\Registry  $registry,
        \Branch8\HotaiShipping\Helper\Data $hotaiShippingHelper,
    )
    {
        parent::__construct($context);
        $this->productRepository = $productRepository;
        $this->_coreRegistry = $registry;
        $this->hotaiShippingHelper = $hotaiShippingHelper;
    }
    public function getProduct(){
        $productId = $this->getRequest()->getParam('id');
        if (!$this->_coreRegistry->registry('product') && $productId) {
            $product = $this->productRepository->getById($productId);
            $this->_coreRegistry->register('product', $product);
        }
        return $this->_coreRegistry->registry('product');
    }

    public function getProductShippingMethod($product){
        $renderData = $this->hotaiShippingHelper->getProductShippingMethod($product);
        return $renderData;
    }

}