<?php

namespace Branch8\MarketplaceProduct\Observer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Catalog\Api\ProductRepositoryInterface;

class TrackImportedProductUser implements ObserverInterface
{
    protected $adminSession;
    protected $productRepository;

    public function __construct(
        AdminSession $adminSession,
        ProductRepositoryInterface $productRepository
    ) {
        $this->adminSession = $adminSession;
        $this->productRepository = $productRepository;
    }

    public function execute(Observer $observer)
    {
        $adminUser = $this->adminSession->getUser();
        if ($adminUser) {
            $adminUsername = $adminUser->getUsername();

            // Get the imported products
            $bunch = $observer->getEvent()->getBunch();
            foreach ($bunch as $productData) {
                try {
                    $product = $this->productRepository->getById($productData['entity_id']);
                    // Set the custom attribute to store the admin username who imported the product
                    $product->setCustomAttribute('admin_user_updated', $adminUsername);
                    $this->productRepository->save($product);
                } catch (\Exception $e) {
                    // Handle the exception if a product cannot be loaded
                    continue;
                }
            }
        }
    }
}
