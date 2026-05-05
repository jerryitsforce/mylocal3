<?php

namespace Branch8\GA4\Block;

use Branch8\GA4\Model\Config;
use Branch8\GA4\Model\Dimension;
use Branch8\GA4\Model\ProductHelper;
use Magento\Framework\View\Element\Template;

class Checkout extends \Branch8\GA4\Block\Core
{
    private $dimensionModel;

    public function __construct(
        Template\Context           $context,
        Config                     $config,
        \Branch8\GA4\Model\Storage $storage,
        ProductHelper              $helper,
        Dimension                  $dimension,
        array                      $data = []
    )
    {
        $this->dimensionModel = $dimension;
        parent::__construct($context, $config, $storage, $helper, $data);
    }

    /**
     * Returns the product details for the purchase gtm event
     * @return array
     */
    public function getProducts()
    {
        /**
         * @var $quote \Magento\Quote\Model\Quote
         */
        $quote = $this->getQuote();
        return $this->productHelper->getGa4CommerceItemList(
            $quote->getAllVisibleItems(), 'quote'
        );
    }

    public function getGa4Total()
    {
        return $this->productHelper->getGa4Total();
    }


    /**
     * @return float
     */
    public function getCartTotal()
    {
        $quote = $this->getQuote();
        return (float)$quote->getGrandTotal();
    }


    public function getProductDimensions($product)
    {
        return $this->dimensionModel->getProductDimensions($product);
    }

}
