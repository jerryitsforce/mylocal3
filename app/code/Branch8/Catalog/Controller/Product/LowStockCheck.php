<?php

namespace Branch8\Catalog\Controller\Product;

use Magento\Catalog\Api\ProductRepositoryInterface;

class LowStockCheck extends \Magento\Framework\App\Action\Action
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface
     */
    protected $stockRegistry;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param ProductRepositoryInterface $productRepository
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        ProductRepositoryInterface $productRepository,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
    )
    {
        parent::__construct($context);
        $this->productRepository = $productRepository;
        $this->stockRegistry = $stockRegistry;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    public function execute()
    {
        $stockDetails = [
            'error' => true,
            'data' => ''
        ];
        $resultJson = $this->resultJsonFactory->create();
        if(!$this->getRequest()->isPost() || !$this->getRequest()->isXmlHttpRequest()){
            return $resultJson->setData($stockDetails);
        }
        $productSku = urldecode($this->getRequest()->getParam('sku'));
        $product = $this->productRepository->get($productSku);
        if(!$product->getId()){
            $stockDetails = [
                'error' => true,
                'data' => ''
            ];
            return $resultJson->setData($stockDetails);
        }
        $productStock = $this->stockRegistry->getStockItem($product->getId());
        if($productStock->getIsInStock() && $productStock->getQty() < 10) {
            $stockDetails = [
                'error' => false,
                'data' => '庫存< 10 件'
            ];
        }else{
            $stockDetails = [
                'error' => false,
                'data' => ''
            ];
        }

        return $resultJson->setData($stockDetails);
    }

}