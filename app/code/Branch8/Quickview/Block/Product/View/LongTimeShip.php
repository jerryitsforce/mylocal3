<?php

namespace Branch8\Quickview\Block\Product\View;

use Magento\Catalog\Api\ProductRepositoryInterface;

class LongTimeShip extends \Magento\Framework\View\Element\Template{

    protected $productRepository;

    protected $_coreRegistry;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        ProductRepositoryInterface $productRepository,
        \Magento\Framework\Registry  $registry
    )
    {
        parent::__construct($context);
        $this->productRepository = $productRepository;
        $this->_coreRegistry = $registry;
    }
    public function getProduct(){
        $productId = $this->getRequest()->getParam('id');
        if (!$this->_coreRegistry->registry('product') && $productId) {
            $product = $this->productRepository->getById($productId);
            $this->_coreRegistry->register('product', $product);
        }
        return $this->_coreRegistry->registry('product');
    }
}