<?php

namespace Branch8\Catalog\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;

class BundleGroupedValidateSeller implements ObserverInterface
{
    protected $mpHelperData;

    protected $messageManager;

    protected $_urlBuilder;

    protected $_responseFactory;

    public function __construct(
        \Webkul\Marketplace\Helper\Data $mpHelperData,
        MessageManagerInterface $messageManager,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\App\ResponseFactory $responseFactory
    ){
        $this->mpHelperData = $mpHelperData;
        $this->messageManager = $messageManager;
        $this->_urlBuilder = $urlBuilder;
        $this->_responseFactory = $responseFactory;
    }

    public function execute($observer){
        $controller = $observer->getControllerAction();
        $request = $controller->getRequest();
        $productPostData = $request->getPost('product');
        $productType = $request->getParam('type');
        if(!in_array($productType, [
                \Magento\Bundle\Model\Product\Type::TYPE_CODE,
                \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE
            ]) || !$productPostData || !isset($productPostData['assign_seller'])){
            return ;
        }
        $assignSeller = $productPostData['assign_seller'];
        $sellerId = $assignSeller['seller_id'];
        $productLinks = $request->getPost('links');

        $isError = false;
        $productType = $request->getParam('type');
        if($productType == \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE){
            $associated = $productLinks['associated'];
            foreach ($associated as $_groupedChild){
                $_groupChildId = $_groupedChild['id'];
                //get seller of child
                $sellerIdOfChild = $this->mpHelperData->getSellerIdByProductId($_groupChildId);
                if($sellerId != $sellerIdOfChild){
                    $isError = true;
                    $this->messageManager->addErrorMessage(__(sprintf('Product "%s" belongs to a different seller than the product being updated.', $_groupedChild['name'])));
                }
            }
        }
        

        if($productType == \Magento\Bundle\Model\Product\Type::TYPE_CODE){
            $bundleOptions = $request->getPost('bundle_options');
            foreach($bundleOptions['bundle_options'] as $opt){
                $optProducts = $opt['bundle_selections'];
                foreach($optProducts as $_optPro){
                    $bundleOptId = $_optPro['product_id'];
                    $sellerIdOfChild = $this->mpHelperData->getSellerIdByProductId($bundleOptId);
                    if($sellerId != $sellerIdOfChild){
                        $isError = true;
                        $this->messageManager->addErrorMessage(__(sprintf('Product "%s" belongs to a different seller than the product being updated.', $_optPro['name'])));
                    }
                }
            }

        }

        if($isError){
            $productId = $controller->getRequest()->getParam('id');
            $productAttributeSetId = $request->getParam('set');
            if($productId) {
                $redirectURL = $this->_urlBuilder->getUrl('catalog/product/edit', ['id' => $productId, '_current' => true, 'set' => $productAttributeSetId]);
            }else{
                $redirectURL = $this->_urlBuilder->getUrl('catalog/product/new', [ '_current' => true, 'set' => $productAttributeSetId]);
            }
            $this->_responseFactory->create()->setRedirect($redirectURL)->sendResponse();
            exit(0);
        }
    }

}