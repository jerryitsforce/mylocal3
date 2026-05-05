<?php

namespace Branch8\Customer\Model\Api;

use Branch8\Customer\Api\Data\BrowsingHistoryProductInterface;
use Branch8\Customer\Api\Data\BrowsingHistoryProductInterfaceFactory;
use Magento\Framework\UrlInterface;
use Magento\Reports\Block\Product\Viewed;
use Magento\Catalog\Helper\Image;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;

class CustomerManagement implements \Branch8\Customer\Api\CustomerManagementInterface
{

    protected Viewed $reportProductViewed;
    protected BrowsingHistoryProductInterfaceFactory $browsingHistoryProductFactory;
    protected Image $imageHelper;
    protected UrlInterface $url;
    protected Emulation $appEmulation;
    private StoreManagerInterface $storeManager;

    public function __construct(
        Viewed $reportProductViewed,
        BrowsingHistoryProductInterfaceFactory $browsingHistoryProductFactory,
        Image $imageHelper,
        UrlInterface $url,
        Emulation $appEmulation,
        StoreManagerInterface $storeManager,

    ) {
        $this->reportProductViewed = $reportProductViewed;
        $this->browsingHistoryProductFactory = $browsingHistoryProductFactory;
        $this->imageHelper = $imageHelper;
        $this->url = $url;
        $this->appEmulation = $appEmulation;
        $this->storeManager = $storeManager;
    }

    /**
     * Get customer browsing history by customer ID.
     *
     * @param int $customerId
     * @return BrowsingHistoryProductInterface[]
     * TODO: paging
     */
    public function getCustomerBrowsingHistory(int $customerId)
    {
        $this->reportProductViewed->setCustomerId($customerId);
        $collection = $this->reportProductViewed->getItemsCollection()
            ->setPageSize(12)
            ->setCurPage(1);


        $browsingHistoryProducts = [];
        $this->appEmulation->startEnvironmentEmulation($this->storeManager->getStore()->getId(), \Magento\Framework\App\Area::AREA_FRONTEND, true);

        foreach ($collection as $product) {
            /** @var BrowsingHistoryProductInterface $browsingHistoryProduct */
            $browsingHistoryProduct = $this->browsingHistoryProductFactory->create();
            $browsingHistoryProduct->setSku($product->getSku())
                ->setName($product->getName())
                ->setUrl($this->getProductUrl($product))
                ->setThumbnail($this->getProductImageUrl($product));

            $browsingHistoryProducts[] = $browsingHistoryProduct;
        }
        $this->appEmulation->stopEnvironmentEmulation();
        return $browsingHistoryProducts;
    }

    public function getProductUrl($product)
    {
        return $product->getUrlModel()->getUrl($product, []);
    }

    public function getProductImageUrl($product)
    {
        return $this->imageHelper->init($product, 'product_base_image')->getUrl();
    }
}
