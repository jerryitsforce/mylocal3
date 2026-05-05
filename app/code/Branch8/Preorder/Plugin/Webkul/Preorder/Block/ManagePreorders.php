<?php

namespace Branch8\Preorder\Plugin\Webkul\Preorder\Block;

class ManagePreorders
{
    protected $productModel;
    public function __construct(
        \Magento\Catalog\Model\ProductFactory $productModel
    ){
        $this->productModel = $productModel;
    }

    public function aroundGetProductData($subject, $process, $id = ''){
        return $this->productModel->create()->load($id);
    }

}