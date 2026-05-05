<?php

namespace Branch8\PromotionPage\Controller\Ajax;

use Branch8\PromotionPage\Widget\CategoryList;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class Badge extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    protected $productCollectionFactory;

    protected $preorderHelper;

    protected $vipHelper;

    /**
     *
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param CategoryList $categoryList
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Webkul\MarketplacePreorder\Helper\Data $preorderHelper,
        \Branch8\PromotionPage\Helper\Data $vipHelper
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->preorderHelper = $preorderHelper;
        $this->vipHelper = $vipHelper;
    }

    public function execute(){
        $resultJson = $this->resultJsonFactory->create();
        $requestItems = $this->getRequest()->getParam('items');
        try {
            $items = explode(',', (string)$requestItems);
            $products = $this->productCollectionFactory->create()
                ->addAttributeToFilter('entity_id', ['in' => $items]);
            $data = [];
            foreach ($products as $_product) {
                $isPreorder = $this->preorderHelper->isPreorder($_product->getId());
                $isVip = $this->vipHelper->isProductInVipCategory($_product);
                $data[$_product->getId()] = [
                    'isPreorder' => $isPreorder,
                    'isVip' => $isVip
                ];
            }
            $dataReturn = ['error' => false, 'data' => $data];
            $resultJson->setData($dataReturn);
        }catch (\Exception $e){
            $dataReturn = ['error' => true];
            $resultJson->setData($dataReturn);
        }

        return $resultJson;
    }
}