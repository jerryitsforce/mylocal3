<?php

namespace Branch8\Catalog\Observer;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CategorySaveAfter implements ObserverInterface
{
    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * @param TypeListInterface $cacheTypeList
     */
    public function __construct(
        TypeListInterface $cacheTypeList
    ) {
        $this->cacheTypeList = $cacheTypeList;
    }

    public function execute(Observer $observer)
    {
        $category = $observer->getEvent()->getCategory();
        if (!$category) {
            return;
        }
        $oldVal = $category->getOrigData('invisible_storefront');
        $newVal = $category->getData('invisible_storefront');

        if ($oldVal != $newVal) {
            // Clean cache
            $this->cacheTypeList->cleanType('full_page');
        }
    }
}
