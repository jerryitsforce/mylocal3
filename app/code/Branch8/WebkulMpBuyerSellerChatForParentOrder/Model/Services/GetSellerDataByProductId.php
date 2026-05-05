<?php

namespace Branch8\WebkulMpBuyerSellerChatForParentOrder\Model\Services;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\ObjectManager;

class GetSellerDataByProductId
{
    private $sellerIds = [];
    private \Magento\Framework\EntityManager\MetadataPool $metadataPool;
    private \Webkul\Marketplace\Helper\Data $mpHelper;

    /**
     * @param \Webkul\Marketplace\Helper\Data $mpHelper
     * @param \Magento\Framework\EntityManager\MetadataPool|null $metadataPool
     * @return void
     */
    public function __construct(
        \Webkul\Marketplace\Helper\Data               $mpHelper,
        \Magento\Framework\EntityManager\MetadataPool $metadataPool = null
    )
    {
        $this->metadataPool = $metadataPool ?: ObjectManager::getInstance()->get(
            \Magento\Framework\EntityManager\MetadataPool::class
        );
        $this->mpHelper = $mpHelper;
    }

    /**
     * Return seller information for this product
     * @param Product $product
     * @return int|mixed
     * @throws \Exception
     */
    public function execute(Product $product)
    {
        if (isset($this->sellerIds[$product->getId()])) {
            return $this->sellerIds[$product->getId()];
        }
        $this->sellerIds[$product->getId()] = (int)$this->mpHelper->getSellerIdByProductId($product->getId());
        return $this->sellerIds[$product->getId()];
    }
}
