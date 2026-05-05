<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Observer;

use Magento\Catalog\Model\Product;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Staging\Model\VersionManager;



class FirstEnabledDateObserver implements ObserverInterface
{
    protected CustomerFactory $customerModel;

    protected LoggerInterface $logger;

    protected ProductFactory $productFactory;

    protected VersionManager $versionManager;

    /**
     * @param CustomerFactory $customerModel
     * @param LoggerInterface $logger
     * @param ProductFactory $productFactory
     */
    public function __construct(
        CustomerFactory $customerModel,
        LoggerInterface $logger,
        ProductFactory $productFactory,
        VersionManager $versionManager
    ) {
        $this->customerModel = $customerModel;
        $this->logger = $logger;
        $this->productFactory = $productFactory;
        $this->versionManager = $versionManager;
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer): void
    {
        /** @var Product $product */
        $mpProduct = $observer->getEvent()->getProduct();
        $productId = $mpProduct->getData('mageproduct_id');
        if (!$productId) {
            $this->logger->error('Product ID is not set for the marketplace product.');
            return;
        }
        $product = $this->productFactory->create()->load($productId);

        if ($mpProduct->getData('is_approved') == 1
            && !$product->getData('first_enabled_date')
            && $product->getStatus() == Status::STATUS_ENABLED
        ) {
            $firstEnabledDate = date('Y-m-d H:i:s');
            $product->setData('first_enabled_date', $firstEnabledDate);
            try {
                $product->save();
                $this->logger->info('Product '.$product->getSku().  ' - First enabled date saved successfully: ' . $firstEnabledDate);
            } catch (\Exception $e) {
                $this->logger->error('Error saving first enabled date: ' . $e->getMessage());
            }
        }
    }

}
