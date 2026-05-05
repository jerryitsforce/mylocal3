<?php

declare(strict_types=1);

namespace Branch8\Catalog\Observer;

use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ProductSaveAfterObserver implements ObserverInterface
{
    protected CategoryLinkManagementInterface $categoryLinkManagement;

    public function __construct(
        CategoryLinkManagementInterface $categoryLinkManagement
    )
    {
        $this->categoryLinkManagement = $categoryLinkManagement;

    }
    public function execute(Observer $observer)
    {

        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getEvent()->getProduct();
        $removedCategories = [];

        // Example: check if the product name has changed
        $originalMainCategory = $product->getOrigData('main_category');
        $currentMainCategory = $product->getData('main_category');
        $originalFlagstoreCategory = $product->getOrigData('flagstore_category');
        $currentFlagstoreCategory = $product->getData('flagstore_category');
        if ($originalMainCategory != $currentMainCategory) {
            $removedCategories[] = $originalMainCategory;
        }
        if ( $originalFlagstoreCategory != $currentFlagstoreCategory) {
            $removedCategories[] = $originalFlagstoreCategory;
        }
        if (empty($removedCategories)) {
            return;
        }

        $newCategory = $product->getData('category_ids');
        $newCategory = array_diff($newCategory, $removedCategories);
        if((int)$currentMainCategory != 0 && !in_array($currentMainCategory, $newCategory)){
            $newCategory[] = $currentMainCategory;
        }
        if((int)$currentFlagstoreCategory != 0 && !in_array($currentFlagstoreCategory, $newCategory)){
            $newCategory[] = $currentFlagstoreCategory;
        }
        $product->setData('category_ids', $newCategory);

        if ($product->getOrigData('entity_id')) {

            $this->categoryLinkManagement->assignProductToCategories(
                $product->getSku(),
                $newCategory
            );
        }

    }
}
