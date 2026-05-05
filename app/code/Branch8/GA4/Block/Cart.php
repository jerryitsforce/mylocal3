<?php
namespace Branch8\GA4\Block;

use Branch8\GA4\Model\Config;
use Branch8\GA4\Model\ProductHelper;
use Magento\Framework\View\Element\Template;

/**
 * Class \WeltPixel\GA4\Block\Cart
 */
class Cart extends \Branch8\GA4\Block\Core
{

    protected $calculatedTotal = 0;
    public function __construct(
        Template\Context $context,
        Config $config,
        ProductHelper $productHelper,
        \Branch8\GA4\Model\Storage $storage,
        array $data = []
    ) {
        parent::__construct($context, $config, $storage, $productHelper, $data);
    }

    /**
     * @return array
     */
    public function getProducts() {
        $quote = $this->getQuote();
        $products = [];
        $index = 0;
        foreach ($quote->getAllVisibleItems() as $item) {
            $product = $item->getProduct();
            $productIdModel = $product;
            $data = $this->productHelper->getDetailProductPush($productIdModel, $index, false);
            $regularPrice = $product->getPriceInfo()->getPrice('regular_price')->getValue();
            $price = $product->getPriceInfo()->getPrice('final_price')->getValue();
            $data['affiliation'] = $this->productHelper->getSellerByProductId($product->getRowId());
            $data['price'] = $this->productHelper->formatMoney($productIdModel->getFinalPrice());
            $data['discount'] = $this->formatMoney($regularPrice > $price ? $regularPrice - $price : 0);
            $data['quantity'] = $this->productHelper->formatQty($item->getQty());
            $this->calculatedTotal += $item->getQty() * $productIdModel->getFinalPrice();
            $index++;
            $products[] = $data;
        }

        return $products;
    }

    public function getGa4Total()
    {
        return $this->calculatedTotal;
    }

    /**
     * @return float
     */
    public function getCartTotal()
    {
        $quote = $this->getQuote();
        $grandTotal = $quote->getGrandTotal() ?? 0;
        return $grandTotal;
    }
}
