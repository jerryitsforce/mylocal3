<?php

declare(strict_types=1);

namespace Branch8\Catalog\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Branch8\MarketplaceProduct\Model\MarketplaceProductManagement;
use Branch8\MarketplaceProduct\Model\ResourceModel\GetProductLogEntryByProductId;
use Branch8\MarketplaceProduct\Api\ProductVersionRepositoryInterface;
use Magento\Framework\App\State;
use Webkul\Marketplace\Model\Product;
use Magento\Framework\App\Area;

class DisapprovePendingApprovalRequest implements ObserverInterface
{
    /**
     * @var MarketplaceProductManagement
     */
    private MarketplaceProductManagement $marketplaceProductManagement;

    /**
     * @var GetProductLogEntryByProductId
     */
    protected GetProductLogEntryByProductId $getProductLogEntryByProductId;

    /**
     * @var ProductVersionRepositoryInterface
     */
    protected ProductVersionRepositoryInterface $productVersionRepository;

    protected $state;
    /**
     *
     * @param MarketplaceProductManagement $marketplaceProductManagement
     * @param GetProductLogEntryByProductId $getProductLogEntryByProductId
     * @param ProductVersionRepositoryInterface $productVersionRepository
     * @param State $state
     */
    public function __construct(
        MarketplaceProductManagement $marketplaceProductManagement,
        GetProductLogEntryByProductId $getProductLogEntryByProductId,
        ProductVersionRepositoryInterface $productVersionRepository,
        State $state
    )
    {
        $this->marketplaceProductManagement = $marketplaceProductManagement;
        $this->getProductLogEntryByProductId = $getProductLogEntryByProductId;
        $this->productVersionRepository = $productVersionRepository;
        $this->state = $state;

    }
    public function execute(Observer $observer)
    {        
        $mageProduct = $observer->getEvent()->getProduct();
        $productId = (int)$mageProduct->getId();
        try {
            // $product = $this->marketplaceProductManagement->getByCode('mageproduct_id', $productId);
            // if ($product->getOrigData('status') == Product::STATUS_PENDING) {$logger->info('pendinggg');
                $logEntry = $this->getProductLogEntryByProductId->execute($productId);
                if (!empty($logEntry['id']) && $logEntry['status'] == Product::STATUS_PENDING) {
                    $productVersion = $this->productVersionRepository->getById((int)$logEntry['id']);
                    $productVersion->setStatus(Product::STATUS_DISABLED);
                    $this->productVersionRepository->save($productVersion);
                }
            // }
        } catch (\Exception $e) {
        }

    }
}
