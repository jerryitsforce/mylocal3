<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Controller\Product;

use Branch8\MarketplaceProduct\Api\ProductTempDataRepositoryInterface;
use Branch8\MarketplaceProduct\Api\ProductVersionRepositoryInterface;
use Branch8\MarketplaceProduct\Model\MarketplaceProductManagement;
use Branch8\MarketplaceProduct\Model\ResourceModel\GetProductLogEntryByProductId;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory as ResultJsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Webkul\Marketplace\Model\Product;

class EditPermission implements ActionInterface, HttpPostActionInterface
{
    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var ResultJsonFactory
     */
    private ResultJsonFactory $resultJsonFactory;

    /**
     * @var MarketplaceProductManagement
     */
    private MarketplaceProductManagement $marketplaceProductManagement;

    /**
     * @var ProductTempDataRepositoryInterface
     */
    protected $productTempDataRepository;

    /**
     * @var ProductVersionRepositoryInterface
     */
    protected ProductVersionRepositoryInterface $productVersionRepository;

    /**
     * @var GetProductLogEntryByProductId
     */
    protected GetProductLogEntryByProductId $getProductLogEntryByProductId;

    /**
     * EditPermission constructor.
     *
     * @param RequestInterface $request
     * @param ResultJsonFactory $resultJsonFactory
     * @param MarketplaceProductManagement $marketplaceProductManagement
     * @param ProductTempDataRepositoryInterface $productTempDataRepository
     * @param ProductVersionRepositoryInterface $productVersionRepository
     * @param GetProductLogEntryByProductId $getProductLogEntryByProductId
     */
    public function __construct(
        RequestInterface             $request,
        ResultJsonFactory            $resultJsonFactory,
        MarketplaceProductManagement $marketplaceProductManagement,
        ProductTempDataRepositoryInterface $productTempDataRepository,
        ProductVersionRepositoryInterface $productVersionRepository,
        GetProductLogEntryByProductId $getProductLogEntryByProductId
    ) {
        $this->request = $request;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->marketplaceProductManagement = $marketplaceProductManagement;
        $this->productTempDataRepository = $productTempDataRepository;
        $this->productVersionRepository = $productVersionRepository;
        $this->getProductLogEntryByProductId = $getProductLogEntryByProductId;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $response = ['status' => false, 'draft' => false];
        try {
            $productId = (int)$this->request->getParam('product_id');
            $product = $this->marketplaceProductManagement->getByCode('mageproduct_id', $productId);
            $response['status'] = $product->getData('status') != Product::STATUS_PENDING;
            $productTempData = $this->productTempDataRepository->get((int)$productId);
            if ($productTempData->getId()) {
                $response['draft'] = true;
            }
            if ($product->getData('status') != Product::STATUS_PENDING) {
                $logEntry = $this->getProductLogEntryByProductId->execute($productId);
                if (!empty($logEntry['id'])) {
                    $productVersion = $this->productVersionRepository->getById((int)$logEntry['id']);
                    $productVersion->setStatus(Product::STATUS_DISABLED);
                    $this->productVersionRepository->save($productVersion);
                }
            }
        } catch (NoSuchEntityException $e) {
        }

        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($response);
    }
}
