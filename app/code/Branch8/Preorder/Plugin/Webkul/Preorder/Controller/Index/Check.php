<?php

namespace Branch8\Preorder\Plugin\Webkul\Preorder\Controller\Index;

use Magento\Framework\App\Action\Context;

class Check
{
    /**
     * @var \Webkul\MarketplacePreorder\Helper\Data
     */
    protected $_preorderHelper;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @param Context $context
     * @param \Webkul\MarketplacePreorder\Helper\Data $preorderHelper
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public function __construct(
        \Webkul\MarketplacePreorder\Helper\Data $preorderHelper,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    ) {
        $this->_preorderHelper = $preorderHelper;
        $this->_resultJsonFactory = $resultJsonFactory;
    }

    public function aroundExecute($subject, $proceed)
    {
        $info = [];
        $helper = $this->_preorderHelper;
        $type = $subject->getRequest()->getParam('type');
        $productId = $subject->getRequest()->getParam('product_id');
        if ($type == 1) {
            $product = $this->_preorderHelper->getProduct($productId);
            if($product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE){
                $attributesInfo = $subject->getRequest()->getParam('info');
                $productId = $helper->getAssociatedId($attributesInfo, $product);
            }
        }

        if ($helper->isPreorder($productId)) {
            $payHtml = $helper->getPayPreOrderHtml($productId);
            $msg = $helper->getPreOrderInfoBlock($productId);
            $info['preorder'] = 1;
            $info['msg'] = $msg;
            $info['payHtml'] = $payHtml;
            $configMsg = __('Product has preorder option(s)');
            $html = "<div class='wk-config-msg-box wk-info'>";
            $html .= $configMsg;
            $html .= '</div>';
            $info['badge'] = '<span class="product-badge preorder">'.__("Preorder").'</span>';
        } else {
            $info['preorder'] = 0;
            $info['badge'] = '';
        }
        $info['stock'] = $helper->getStockDetails($productId);


        $result = $this->_resultJsonFactory->create();
        $result->setData($info);
        return $result;
    }
}
