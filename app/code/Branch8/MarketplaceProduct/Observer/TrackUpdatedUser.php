<?php

declare(strict_types=1);

namespace Branch8\MarketplaceProduct\Observer;

use Magento\Catalog\Model\Product;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\ProductFactory;


class TrackUpdatedUser implements ObserverInterface
{
    protected CustomerFactory $customerModel;

    protected LoggerInterface $logger;

    protected ProductFactory $productFactory;

    /**
     * @param CustomerFactory $customerModel
     * @param LoggerInterface $logger
     * @param ProductFactory $productFactory
     */
    public function __construct(
        CustomerFactory $customerModel,
        LoggerInterface $logger,
        ProductFactory $productFactory
    ) {
        $this->customerModel = $customerModel;
        $this->logger = $logger;
        $this->productFactory = $productFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer): void
    {
        /** @var Product $product */
        $mpProduct = $observer->getEvent()->getProduct();
        $productId = $mpProduct->getData('mageproduct_id');
        $userUpdated = $observer->getEvent()->getUserUpdated();
        $product = $this->productFactory->create()->load($productId);
        if (!$product->getId()) {
            return;
        }
        $seller = $observer->getEvent()->getSeller();
        $sellerId = $seller->getId();

        $lastUpdatedUser = $product->getData('admin_user_updated');
        if ($userUpdated) {
            if ($userUpdated != $lastUpdatedUser) {
                try {
                    $product->setCustomAttribute('admin_user_updated', $userUpdated);
                    $product->save();
                } catch (\Exception $e) {
                    $this->logger->error('Cannot update admin_user_updated');
                    $this->logger->critical($e);
                }
            }
        } else {
            $customer = $this->customerModel->create()->load($sellerId);
            $sellerName = $customer->getData('prefix') . " " . $customer->getFirstname() . ' ' . $customer->getLastname();
            if ($sellerName && $sellerName != $lastUpdatedUser) {
                // Set the custom attribute value
                try {
                    $product->setCustomAttribute('admin_user_updated', $sellerName);
                    $product->save();
                } catch (\Exception $e) {
                    $this->logger->error('Cannot update admin_user_updated');
                    $this->logger->critical($e);
                }
            }
        }
    }

}
