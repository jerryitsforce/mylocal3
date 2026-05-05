<?php

declare(strict_types=1);

namespace Branch8\SellerInformationProductDataExport\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\State;

class SyncSellerIdToindexSellerId implements ObserverInterface
{
    /**
     * @var
     */
    private $service;

    /**
     * @var State
     */
    private $state;

    /**
     * @param \Branch8\SellerInformationProductDataExport\Model\Services\SyncSellerIdToIndexSellerId $service
     * @param State $state
     */
    public function __construct(
        \Branch8\SellerInformationProductDataExport\Model\Services\SyncSellerIdToIndexSellerId $service,
        State                                                                                  $state
    )
    {
        $this->service = $service;
        $this->state = $state;
    }

    /**
     * Process event on 'save_commit_after' event
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var $product \Magento\Catalog\Model\Product */
        $product = $observer->getEvent()->getProduct();
        if ($this->state->isAreaCodeEmulated()) {
            return;
        }
        // Always sync to ensure index_seller_id stays in sync with marketplace_product.seller_id. - fix HTGO2-3311
        $this->service->syncIds([$product->getId()]);
        // move to indexer
        //if (empty($product->getData("livesearch_categories"))) {
            //$this->service->syncLivesearchCategoriesIds([$product->getId()]);
        //}
        // Always sync to ensure seller_shop_name stays up-to-date when seller changes shop title
        $this->service->syncShopNameIds([$product->getId()]);
        // move to indexer
        //$this->service->syncLivesearchInstockIds([$product->getId()]);
        //$this->service->syncNeedToRefillIds([$product->getId()]);
    }
}
