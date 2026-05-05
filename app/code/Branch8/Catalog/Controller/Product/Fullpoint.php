<?php

namespace Branch8\Catalog\Controller\Product;

use Magento\Catalog\Api\ProductRepositoryInterface;

class Fullpoint extends \Magento\Framework\App\Action\Action
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;
    /**
     * @var \Branch8\Checkout\Helper\FullpointCheckout
     */
    protected $fullpointCheckoutHelper;

    protected $pointMoneyHelper;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Branch8\Checkout\Helper\FullpointCheckout $fullpointCheckoutHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        ProductRepositoryInterface $productRepository,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Branch8\Checkout\Helper\FullpointCheckout $fullpointCheckoutHelper,
        \Branch8\PointMoneyCollect\Helper\Data $pointMoneyHelper
    )
    {
        parent::__construct($context);
        $this->productRepository = $productRepository;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->fullpointCheckoutHelper = $fullpointCheckoutHelper;
        $this->pointMoneyHelper = $pointMoneyHelper;
    }

    public function execute()
    {
        $data = [
            'is_virtual' => false,
            'is_full_point' => false,
            'is_enable_full_point_checkout' => false,
            'is_logged_in' => false,
            'point_rate' => $this->pointMoneyHelper->getPointConvertRate()
        ];
        $resultJson = $this->resultJsonFactory->create();
        if(!$this->getRequest()->isXmlHttpRequest()){
            return $resultJson->setData($data);
        }
        $productSku = urldecode($this->getRequest()->getParam('sku'));
        $product = $this->productRepository->get($productSku);
        if(!$product->getId()){
            return $resultJson->setData($data);
        }

        $data = $this->fullpointCheckoutHelper->getFullPointCheckoutProduct($product);
        $data['is_logged_in'] = $this->fullpointCheckoutHelper->isLoggedIn();
        return $resultJson->setData($data);
    }

}