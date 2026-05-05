<?php
/**
 * Magetop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magetop.com license that is
 * available through the world-wide-web at this URL:
 * https://www.magetop.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magetop
 * @package     Magetop_Quickview
 * @copyright   Copyright (c) Magetop (https://www.magetop.com/)
 * @license     https://www.magetop.com/LICENSE.txt
 */
namespace Magetop\Quickview\Helper;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var array
     */
    protected $quickviewOptions;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlInterface;

    /**
     * @var string
     */
    public $scopeStore = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

    /**
     * Data constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($context);
        $this->scopeConfig = $context->getScopeConfig();
        $this->urlInterface = $context->getUrlBuilder();
    }

    /**
     * Btn Text color
     *
     * @return mixed|string
     */
    public function getBtnTextColor()
    {
        $color = $this->scopeConfig->getValue(
            'magetop_quickview/success_popup_design/button_text_color',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $color = ($color == '') ? '' : $color;
        return $color;
    }

    /**
     * Btn background
     *
     * @return mixed|string
     */
    public function getBtnBackground()
    {
        $backGround = $this->scopeConfig->getValue(
            'magetop_quickview/success_popup_design/background_color',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $backGround = ($backGround == '') ? '' : $backGround;
        return $backGround;
    }

    /**
     * Button text
     *
     * @return \Magento\Framework\Phrase|mixed
     */
    public function getButtonText()
    {
        $buttonText = $this->scopeConfig->getValue(
            'magetop_quickview/success_popup_design/button_text',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $buttonText = ($buttonText == '') ? __('Quick View') : $buttonText;
        return $buttonText;
    }

    /**
     * Enabled module
     *
     * @return mixed|string
     */
    public function enabled()
    {
        $isEnabled = $this->scopeConfig->getValue(
            'magetop_quickview/general/enable_product_listing',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $isEnabled = ($isEnabled == '') ? '' : $isEnabled;
        return $isEnabled;
    }

    /**
     * Get Url
     *
     * @return string
     */
    public function getUrl()
    {
        $productUrl = $this->urlInterface->getUrl('magetop_quickview/catalog_product/view/');
        return $productUrl;
    }

    /**
     * Get base url
     *
     * @return string
     */
    public function getBaseUrl()
    {
        $baseUrl = $this->urlInterface->getUrl();
        return $baseUrl;
    }

    /**
     * Get Enable Remove Reivews
     *
     * @return string
     */
    public function getRemoveReview()
    {
        $data = $this->scopeConfig->getValue(
            'magetop_quickview/general/remove_reviews',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return $data;
    }

    /**
     * Get Enable Remove More Information
     *
     * @return string
     */
    public function getRemoveMoreInfo()
    {
        $data = $this->scopeConfig->getValue(
            'magetop_quickview/general/remove_product_tab',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return $data;
    }

    /**
     * Get sku template
     *
     * @return string
     */
    public function getSkuTemplate()
    {
        $this->quickviewOptions = $this->scopeConfig->getValue(
            'magetop_quickview',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $removeSku = $this->quickviewOptions['general']['remove_sku'];
        if (!$removeSku) {
            return 'Magento_Catalog::product/view/attribute.phtml';
        }

        return '';
    }

    /**
     * Get Custom css
     *
     * @return string
     */
    public function getCustomCSS()
    {
        $this->quickviewOptions = $this->scopeConfig->getValue(
            'magetop_quickview',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return trim($this->quickviewOptions['general']['custom_css']);
    }

    /**
     * Get close seconds
     *
     * @return int
     */
    public function getCloseSeconds()
    {
        $this->quickviewOptions = $this->scopeConfig->getValue(
            'magetop_quickview',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        return trim($this->quickviewOptions['general']['close_quickview']);
    }

    /**
     * Get product Image
     *
     * @return mixed
     */
    public function getProductImageWrapper()
    {
        $result = $this->scopeConfig->getValue('magetop_quickview/seting_theme/product_image_wrapper', $this->scopeStore);
        if ($result == null) {
            $result = 'product-image-wrapper';
        }
        return $result;
    }

    /**
     * Get Product Item Info
     *
     * @return mixed|string
     */
    public function getProductItemInfo()
    {
        $result = $this->scopeConfig->getValue('magetop_quickview/seting_theme/product_item_info', $this->scopeStore);
        if ($result == null) {
            $result = 'product-item-info';
        }
        return $result;
    }
}
