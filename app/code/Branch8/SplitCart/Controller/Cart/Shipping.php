<?php

namespace Branch8\SplitCart\Controller\Cart;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\Result\PageFactory;
class Shipping extends Action{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;
    /**
     * @var \Branch8\SplitCart\Helper\Data
     */
    protected $splitCartHelper;
    /**
     * @var \Webkul\Marketplace\Helper\Data
     */
    protected $marketPlaceDataHelper;
    /**
     * @var \Branch8\SplitCart\Helper\Data
     */
    protected $splitCartHelperData;

    public function __construct(
        JsonFactory $resultJsonFactory,
        PageFactory $resultPageFactory,
        \Branch8\SplitCart\Helper\Data $splitCartHelper,
        \Webkul\Marketplace\Helper\Data $mpDataHelper,
        \Branch8\SplitCart\Helper\Data  $splitCartHelperData,
        Context $context){
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resultPageFactory = $resultPageFactory;
        $this->splitCartHelper = $splitCartHelper;
        $this->marketPlaceDataHelper = $mpDataHelper;
        $this->splitCartHelperData = $splitCartHelperData;
    }

    public function execute(){
        $resultJson = $this->resultJsonFactory->create();
        try {
            $resultPage = $this->resultPageFactory->create();
            $contentBlock = $resultPage->getLayout()->getBlock('shipping_infor');

            $cartType = $this->getRequest()->getParam('cartType');
            $sellerId = (int)$this->getRequest()->getParam('sid');
            if(in_array($cartType, [\Branch8\SplitCart\Helper\Data::TYPE_PREORDER_VIRTUAL, \Branch8\SplitCart\Helper\Data::TYPE_VIRTUAL])){
                return $resultJson->setData(['error' => false, 'dataHtml' => '']);
            }else {
                $items = $this->splitCartHelper->getItemsCartType($cartType, $sellerId);
                $seller = $this->splitCartHelperData->getSellerBySellerId($sellerId);
                $contentBlock->setData('items', $items)
                    ->setData('cartType', $cartType)
                    ->setData('seller', $seller);
            }

        }catch (\Exception $exception){
            return $resultJson->setData(['error' => true]);
        }
        return $resultJson->setData(['error' => false, 'dataHtml' => $contentBlock->toHtml()]);
    }
}