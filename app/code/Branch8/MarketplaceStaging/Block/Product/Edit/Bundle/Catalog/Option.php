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

namespace Branch8\MarketplaceStaging\Block\Product\Edit\Bundle\Catalog;

use Magento\CatalogStaging\Model\Product\Locator\StagingLocator;
use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Mp bundle block catalog class
 */
class Option extends \Magento\Bundle\Block\Adminhtml\Catalog\Product\Edit\Tab\Bundle\Option
{
    /**
     * Form element
     *
     * @var AbstractElement|null
     */
    protected $_element = null;

    /**
     * List of bundle product options
     *
     * @var array|null
     */
    protected $_options = null;

    /**
     * @var string
     */
    protected $_template = 'Branch8_MarketplaceStaging::product/edit/bundle/options/option.phtml';

    /**
     * @var StagingLocator
     */
    protected $_stagingProduct;

    /**
     * @var \Magento\Bundle\Model\Source\Option\Type
     */
    protected $_optionTypes;

    /**
     * @var \Magento\Config\Model\Config\Source\Yesno
     */
    protected $_yesno;
    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $_proModel;

    /**
     * @var \Webkul\MpBundleProduct\Helper\Data
     */
    protected $bundleHelper;

    /**
     * @var \Magento\Catalog\Helper\Product
     */
    protected $proHelper;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Catalog\Model\Product $proModel
     * @param \Magento\Config\Model\Config\Source\Yesno $yesno
     * @param \Magento\Bundle\Model\Source\Option\Type $optionTypes
     * @param \Magento\Framework\Registry $registry
     * @param \Webkul\MpBundleProduct\Helper\Data $bundleHelper
     * @param \Magento\Catalog\Helper\Product $proHelper
     * @param StagingLocator $product
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Catalog\Model\Product $proModel,
        \Magento\Config\Model\Config\Source\Yesno $yesno,
        \Magento\Bundle\Model\Source\Option\Type $optionTypes,
        \Magento\Framework\Registry $registry,
        \Webkul\MpBundleProduct\Helper\Data $bundleHelper,
        \Magento\Catalog\Helper\Product $proHelper,
        StagingLocator $product,
        array $data = []
    ) {
        $this->_proModel = $proModel;
        $this->_optionTypes = $optionTypes;
        $this->_yesno = $yesno;
        $this->bundleHelper = $bundleHelper;
        $this->proHelper = $proHelper;
        $this->_stagingProduct = $product;
        parent::__construct($context, $yesno, $optionTypes, $registry, $data);
    }

    /**
     * PrepareLayout
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $this->addChild(
            'selection_template',
            \Branch8\MarketplaceStaging\Block\Product\Edit\Bundle\Catalog\Selection::class
        );
    }

    /**
     * GetProductType
     *
     * @return product type
     * @param int $productId
     */
    protected function getProductType($productId)
    {
        $productType = $this->_proModel->load($productId)->getTypeId();
        return $productType;
    }

    /**
     * Get Product Type
     *
     * @param int $productId
     * @return string
     */
    public function getProductTypeById($productId)
    {
        return $this->bundleHelper->getProductTypeById($productId);
    }

    /**
     * Encode Data into Json
     *
     * @param array $data
     * @return Json
     */
    public function jsonEncodeData($data)
    {
        return $this->bundleHelper->jsonEncodeData($data);
    }

     /**
      * Product Helper Object
      *
      * @return \Magento\Catalog\Helper\Product
      */
    public function productHelper()
    {
        return $this->proHelper;
    }

    /**
     * Get Type Select Html
     *
     * @return mixed
     */
    public function getTypeSelectHtml()
    {
        $select = $this->getLayout()->createBlock(
            \Magento\Framework\View\Element\Html\Select::class
        )->setData(
            [
                'id' => 'staging_' . $this->getFieldId() . '_<%- data.index %>_type',
                'class' => 'select select-product-option-type required-option-select',
                'extra_params' => 'onchange="bOption.changeType(event)"',
            ]
        )->setName(
            $this->getFieldName() . '['.$this->getFieldName().'][<%- data.index %>][type]'
        )->setOptions(
            $this->_optionTypes->toOptionArray()
        );

        return $select->getHtml();
    }

    /**
     * Get Require Select Html
     *
     * @return mixed
     */
    public function getRequireSelectHtml()
    {
        $select = $this->getLayout()->createBlock(
            \Magento\Framework\View\Element\Html\Select::class
        )->setData(
            ['id' => 'staging_' . $this->getFieldId() . '_<%- data.index %>_required', 'class' => 'select']
        )->setName(
            $this->getFieldName() . '['.$this->getFieldName().'][<%- data.index %>][required]'
        )->setOptions(
            $this->_yesno->toOptionArray()
        );

        return $select->getHtml();
    }

    /**
     * Retrieve Product object
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function getProduct()
    {
        return $this->_stagingProduct->getProduct();
    }
}
