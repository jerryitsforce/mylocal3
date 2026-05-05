<?php

namespace Branch8\Smtp\Block\AbandonedCart;

use Magento\Framework\View\Element\Template;
use \Magento\Catalog\Helper\Image;
use Mageplaza\Smtp\Helper\EmailMarketing;

class AbandonedCart extends Template
{
    protected $_template = 'Branch8_Smtp::abandonedCart.phtml';

    /**
     * @var Image
     */
    protected $imageHelper;

    /**
     * @var EmailMarketing
     */
    protected $helperEmailMarketing;

    /**
     * AbandonedCart Template Constructor
     * @param Template\Context $context
     * @param Image $imageHelper
     * @param EmailMarketing $helperEmailMarketing
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Image $imageHelper,
        EmailMarketing $helperEmailMarketing,
        array $data = []
    ) {
        $this->helperEmailMarketing = $helperEmailMarketing;
        $this->imageHelper = $imageHelper;
        parent::__construct($context, $data);
    }

    /**
     * Get product image url
     * @param $product
     * @return string
     */
    public function getProductImageUrl($product)
    {
        return $this->imageHelper->init($product, "product_thumbnail_image")->getUrl();
    }

    /**
     * Get cart item option
     * @param $item
     * @return string
     */
    public function getProductOption($item)
    {
        $options = $this->helperEmailMarketing->getProductOptions($item);
        $optionTxt = "";
        if ($options) {
            foreach ($options as $_option) {
                $optionValue = $this->helperEmailMarketing->getFormatedOptionValue($_option);
                $optionTxt = $_option['label'].": ".$optionValue['value'];
            }
        }
        return $optionTxt;
    }
}
