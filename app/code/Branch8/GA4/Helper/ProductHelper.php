<?php
namespace Branch8\GA4\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\DataObject;
use Magento\Store\Model\StoreManagerInterface;

class ProductHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected StoreManagerInterface $storeManager;
    private \Branch8\GA4\Model\ProductHelper $productHelperModel;

    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        \Branch8\GA4\Model\ProductHelper $productHelperModel,
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->productHelperModel = $productHelperModel;
    }

    /**
     * @param $product
     * @param bool $includeGa4Settings
     * @return string
     */
    public function getGA4ItemJson($product, $index = 1, bool $includeGa4Settings = false)
    {
        $data = $this->productHelperModel->getDetailProductPush($product);
        $regularPrice = $product->getPriceInfo()->getPrice('regular_price')->getValue();
        $price = $product->getPriceInfo()->getPrice('final_price')->getValue();



        $data['price'] = $price;
        $data['affiliation'] = $this->productHelperModel->getSellerByProductId($product->getRowId(), $product);
        $data['discount'] = $this->productHelperModel->formatMoney($regularPrice > $price ? $regularPrice - $price : 0);
        $data['quantity'] = 1;
        $data['index'] = $index;

        if(!$includeGa4Settings) {
            unset($data['item_list_id']);
            unset($data['item_list_name']);
            unset($data['promotion_id']);
            unset($data['promotion_name']);
        }

        return json_encode($data, JSON_UNESCAPED_UNICODE
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT);
    }
}
