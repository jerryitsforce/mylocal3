<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_MpBundleProduct
 * @author    Webkul Software Private Limited
 * @copyright Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
namespace Branch8\MarketplaceStaging\Block\Product\Edit\Bundle\Init;

use Magento\CatalogStaging\Model\Product\Locator\StagingLocator;

/**
 * Adminhtml catalog product bundle items tab block
 */
class Bundle extends \Magento\Backend\Block\Widget implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * @var \Magento\Catalog\Model\Product|null
     */
    protected $_product = null;

    /**
     * @var string
     */
    protected $_template = 'Branch8_MarketplaceStaging::product/edit/bundle/init.phtml';

    /**
     * @var StagingLocator
     */
    protected $_stagingProduct;
    /**
     * @var \Webkul\MpBundleProduct\Helper\Data
     */
    protected $_helper;

    /**
     * @param \Magento\Backend\Block\Template\Context   $context
     * @param \Webkul\MpBundleProduct\Helper\Data       $helper
     * @param StagingLocator                            $product
     * @param array                                     $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Webkul\MpBundleProduct\Helper\Data $helper,
        StagingLocator $product,
        array $data = []
    ) {
        $this->_stagingProduct = $product;
        $this->_helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * GetTabClass
     *
     * @return string
     */
    public function getTabClass()
    {
        return 'ajax';
    }

    /**
     * Prepare layout
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $this->setData('opened', true);
        $this->addChild(
            'add_button',
            \Magento\Backend\Block\Widget\Button::class,
            [
                'label' => __('Add Option'),
                'class' => 'add',
                'id' => 'add_new_option',
                'on_click' => 'bStageOption.add()'
            ]
        );

        $this->setChild(
            'options_box',
            $this->getLayout()->createBlock(
                \Branch8\MarketplaceStaging\Block\Product\Edit\Bundle\Catalog\Option::class,
                'StageBundle.select.product.option'
            )
        );
        return parent::_prepareLayout();
    }

    /**
     * Check block readonly
     *
     * @return boolean
     */
    public function isReadonly()
    {
        return $this->getProduct()->getCompositeReadonly();
    }

    /**
     * GetOptionsBoxHtml
     *
     * @return string
     */

    public function getOptionsBoxHtml()
    {
        return $this->getChildHtml('options_box');
    }

    /**
     * GetFieldSuffix
     *
     * @return string
     */
    public function getFieldSuffix()
    {
        return 'product';
    }

    /**
     * GetProduct
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function getProduct()
    {
        return $this->_stagingProduct->getProduct();
    }

    /**
     * GetTabLabel
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Bundle Items');
    }

    /**
     * GetTabTitle
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Bundle Items');
    }

    /**
     * CanShowTab
     *
     * @return bool
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * IsHidden
     *
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Get parent tab code
     *
     * @return string
     */
    public function getParentTab()
    {
        return 'product-details';
    }
}
