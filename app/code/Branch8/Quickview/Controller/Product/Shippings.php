<?php

namespace Branch8\Quickview\Controller\Product;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class Shippings extends \Magento\Framework\App\Action\Action{

    protected $_productFactory;

    protected $hotaiShippingHelper;

    protected $resultJsonFactory;

    protected $_configurable;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Catalog\Model\ProductFactory $product,
        Configurable $configurable,
        \Branch8\HotaiShipping\Helper\Data $hotaiShippingHelper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ){
        parent::__construct($context);
        $this->_productFactory = $product;
        $this->_configurable = $configurable;
        $this->hotaiShippingHelper = $hotaiShippingHelper;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    public function execute(){
        // TODO: Implement execute() method.
        $parentId = $this->getRequest()->getParam('id');
        $result = ['error' => 0];
        $parentProduct = $this->_productFactory->create()->load($parentId);
        if($parentProduct->getTypeId() != \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE){
            $result['error'] = 1;
            return $this->resultJsonFactory->create()->setData($result);
        }
        $attributesInfo = $this->getRequest()->getParam('info');
        $childProductId = $this->getAssociatedId($attributesInfo, $parentProduct);
        $childProduct = $this->_productFactory->create()->load($childProductId);
        $shippingInfor = $this->hotaiShippingHelper->getProductShippingMethod($childProduct);
        $result['data'] = $shippingInfor;
        return $this->resultJsonFactory->create()->setData($result);
    }

    protected function getAssociatedId($attributesInfo, $parentProduct){
        $product = $this->_configurable->getProductByAttributes($attributesInfo, $parentProduct);
        $productId = $product->getId();
        return $productId;
    }
}