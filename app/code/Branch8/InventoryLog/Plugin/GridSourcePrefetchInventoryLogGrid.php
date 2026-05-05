<?php

namespace Branch8\InventoryLog\Plugin;

use Magento\Framework\Registry;

class GridSourcePrefetchInventoryLogGrid
{
    protected $request;

    protected $productRepository;

    protected $registry;
    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        Registry $registry
    ){
        $this->request = $request;
        $this->productRepository = $productRepository;
        $this->registry = $registry;
    }
    public function beforeExecute($subject, $observer){
        $pid = $this->request->getParam('id');
        if(!$pid){
            return [$observer];
        }
        $product = $this->productRepository->getById($pid);
        if(!$this->registry->registry('current_product')) {
            $this->registry->register('current_product', $product);
        }
        return [$observer];
    }

}