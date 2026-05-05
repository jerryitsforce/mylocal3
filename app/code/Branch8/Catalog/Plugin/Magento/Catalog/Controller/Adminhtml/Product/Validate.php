<?php
namespace Branch8\Catalog\Plugin\Magento\Catalog\Controller\Adminhtml\Product;

class Validate {

    public function beforeExecute($subject){
        $request = $subject->getRequest();
        $typeId = $request->getParam('type', '');
        if($typeId == 'virtual'){
            $productData = $request->getPost('product', []);
            $productData['shipping_method'] = ['electronic'];
            $request->setPostValue('product', $productData);
        }
    }
}