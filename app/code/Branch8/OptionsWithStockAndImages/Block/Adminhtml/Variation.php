<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_OptionsWithStockAndImages
 * @author    Webkul Software Private Limited
 * @copyright Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Branch8\OptionsWithStockAndImages\Block\Adminhtml;

class Variation extends \Magento\Backend\Block\Template
{
    /**
     * @var \Webkul\OptionsWithStockAndImages\Model\VariationsFactory
     */
    protected $variationsFactory;

    /**
     * @var \Webkul\OptionsWithStockAndImages\Model\SwatchFactory
     */
    protected $swatchFactory;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlInterface;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManagerInterface;

    /**
     * @var \Webkul\OptionsWithStockAndImages\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * __construct
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\UrlInterface $urlInterface
     * @param \Webkul\OptionsWithStockAndImages\Model\VariationsFactory $variationsFactory
     * @param \Webkul\OptionsWithStockAndImages\Model\SwatchFactory $swatchFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Webkul\OptionsWithStockAndImages\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\UrlInterface $urlInterface,
        \Webkul\OptionsWithStockAndImages\Model\VariationsFactory $variationsFactory,
        \Webkul\OptionsWithStockAndImages\Model\SwatchFactory $swatchFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Webkul\OptionsWithStockAndImages\Helper\Data $helper,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {

        $this->variationsFactory = $variationsFactory;
        $this->swatchFactory = $swatchFactory;
        $this->_urlInterface = $urlInterface;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->helper = $helper;
        $this->registry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Get Image Upload Url
     *
     * @return string
     */
    public function getImageUrl()
    {
        return $this->_urlInterface->getUrl('optionswithstockandimages/image/upload');
    }

    /**
     * Get Image Upload Url
     *
     * @return string
     */
    public function getSyncUrl()
    {
        return $this->_urlInterface->getUrl('b8optionswithstockandimages/ajax/getdatasync');
    }

    /**
     * Check the module is enable or not
     *
     * @return bool
     */
    public function isModuleEnable()
    {
        return $this->helper->isEnable();
    }
    /**
     * Get saved product data
     *
     * @return string
     */
    public function getSavedData()
    {
        $productId = $this->getProduct()->getRowId();
        if ($productId) {
            $collection = $this->variationsFactory->create()
                                    ->getCollection()
                                    ->addFieldToFilter("product_id", $productId);
            if ($collection->getSize()) {
                $data = $collection->getData();
                foreach ($data as $key => $value) {
                    $data[$key]['image'] = explode(',', $data[$key]['image']);
                    if (empty($data[$key]['file']) && is_array($data[$key]['image'])) {
                        $data[$key]['file'] = $data[$key]['image'];
                    } else {
                        $data[$key]['file'] = [];
                    }
                }
                return $this->helper->jsonEncode($data);
            }
        }
        return '{}';
    }

    /**
     * Get path of uploaded images
     *
     * @return string
     */
    public function getMediaUrl()
    {
        return $this->storeManagerInterface->getStore()
                    ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).'wkosi/products';
    }

    /**
     * Get Swatch Data
     *
     * @return string
     */
    public function getSwatchData()
    {
        $productId = $this->getProduct()->getRowId();
        if ($productId) {
            $collection = $this->swatchFactory->create()
                                        ->getCollection()
                                        ->addFieldToFilter("product_id", $productId);
            if ($collection->getSize()) {
                return $this->helper->jsonEncode($collection->getData());
            }
        }
        return '{}';
    }

    /**
     * Get Product
     *
     * @return \Magento\Catalog\Model\Product
     */
    public function getProduct()
    {
        return $this->registry->registry('product');
    }
    /**
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getTmpMediaUrl()
    {
        return $this->storeManagerInterface->getStore()
                ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA).'tmp/catalog/product';
    }
}
