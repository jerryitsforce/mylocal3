<?php

namespace Branch8\Catalog\Observer;

use Branch8\Quote\Model\Actions\GetScheduleSpecialPriceMetaInformation;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class TrackMainCategory implements ObserverInterface
{
    private $categoryRepository;

    /**
     * @param CategoryRepository $categoryRepository
     */
    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @throws \Zend_Db_Statement_Exception
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /**
         * @var $quoteItem \Magento\Quote\Model\Quote\Item
         */
        $quoteItem = $observer->getEvent()->getItem();
        $quote = $quoteItem->getQuote();
        if (!$quoteItem->getId() && $quoteItem->getProduct()) {
            $mainCategory = $quoteItem->getProduct()->getData('main_category');
            $category = $this->getCategoryName($mainCategory, $quote->getStoreId());
            if ($mainCategory) {
                $quoteItem->setData('main_category', $mainCategory);
            }
            if ($category) {
                $quoteItem->setData('main_category_name', $category->getName());
            }
        }
    }

    /**
     * @param $id
     * @param $storeId
     * @return \Magento\Catalog\Api\Data\CategoryInterface|mixed|string|null
     */
    private function getCategoryName($id, $storeId)
    {
        try {
            return $this->categoryRepository->get($id, $storeId);
        } catch (NoSuchEntityException $exception) {
            return '';
        }
    }
}
