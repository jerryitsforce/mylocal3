<?php

namespace Branch8\ProductAlert\Helper;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Cms\Model\Template\FilterProvider;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Shipping\Model\Config;
use Magento\Store\Model\StoreManagerInterface;

class Data extends AbstractHelper
{
    const EIGHTEEN_PRODUCT = "eighteen_product";
    const ENABLED = 1;
    const ALERT_IMAGE = "product_alert/general_settings/image";
    const ALERT_TEXT = "product_alert/general_settings/text";

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $_categoryCollectionFactory;

    /**
     * @var \Magento\Cms\Model\Template\FilterProvider
     */
    protected $filterProvider;

    /**
     * @var \Magento\Shipping\Model\Config
     */
    protected $_shippingConfig;

    /**
     * @var mixed
     */
    protected $cachedAlertImage;

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param CollectionFactory $productCollectionFactory
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
     * @param Config $shippingConfig
     * @param FilterProvider $filterProvider
     */
    public function __construct(
        Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Shipping\Model\Config $shippingConfig,
        FilterProvider $filterProvider
    )
    {
        $this->filterProvider = $filterProvider;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_shippingConfig = $shippingConfig;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * getAllOptions
     *
     * @return array
     */
    public function getShippingAllMethodsOptions()
    {
        $carriers_data = [];
        $carriers = $this->_shippingConfig->getAllCarriers();
        foreach ($carriers as $carrierCode => $carrierModel) {
            if (!$carrierModel->isActive()) {
                continue;
            }
            $carrierMethods = $carrierModel->getAllowedMethods();
            if (!$carrierMethods) {
                continue;
            }
            foreach ($carrierMethods as $methodCode => $methodTitle) {
                /** Check it $carrierMethods array was well formed */
                if (!$methodCode) {
                     continue;
                }
                $carriers_data[] = [
                    'value' => $carrierCode . '_' . $methodCode,
                    'label' => $methodTitle,
                ];
            }
        }
        $carriers_data[] = [
            'value' => 'electronic',
            'label' => __('Electronic tickets'),
        ];

        return json_encode(array_filter($carriers_data));
    }

    /**
     * @return string
     */
    public function getProductAlertImage()
    {
        if (!$this->cachedAlertImage) {
            $mediaUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
            $value = $this->scopeConfig->getValue(self::ALERT_IMAGE);
            $this->cachedAlertImage = $mediaUrl . 'images/' . $value;
        }
        return $this->cachedAlertImage;
    }

    /**
     * @return mixed
     */
    public function getAlertContent() {
        $value = $this->scopeConfig->getValue(self::ALERT_TEXT);
        if($value){
            return $this->filterProvider->getPageFilter()->filter($value);
        }
        return '';
    }

    /**
     * Retrieve SKUs of products with 'Eighteen Product' attribute enabled
     * If $productId is provided, return the 'Eighteen Product' attribute of the product with the given ID
     *
     * @param int|null $productId
     * @return array|bool Array of SKUs of products or 'Eighteen Product' attribute of the product with the given ID
     */
    public function getEighteenProducts($productId = null) {
        // Create product collection factory instance
        $collection = $this->_productCollectionFactory->create();
        // Select all attributes for product
        $collection->addAttributeToSelect('*');
        // Add filter to get only products with 'Eighteen Product' attribute enabled
        $collection->addAttributeToFilter(self::EIGHTEEN_PRODUCT, self::ENABLED);

        // If $productId is provided, add filter to get the product with the given ID
        if ($productId) {
            $collection->addAttributeToFilter('entity_id', $productId);
            $productData = $collection->getData();
            // Return the 'Eighteen Product' attribute of the product with the given ID
            return isset($productData[0][self::EIGHTEEN_PRODUCT]) && $productData[0][self::EIGHTEEN_PRODUCT];
        }

        // Retrieve data of filtered products
        $productData = $collection->getData();
        // Initialize array to store SKUs
        $skus = [];
        // Iterate through product data to extract SKUs
        foreach ($productData as $product) {
            $skus[] = $product['sku']; // Add SKU to array
        }
        // Return array of SKUs
        return $skus;
    }

    /**
     * @return mixed
     */
    public function getCategoriesInvisible()
    {
        try {
            $categories = $this->_categoryCollectionFactory->create();
            $categories->addAttributeToSelect('*');
            $categories->addIsActiveFilter();
            $categories->addAttributeToFilter('invisible_storefront', 1);
            if ($categories->getSize()) {
                foreach ($categories as $category) {
                    $result[] = (string)$category->getUrlPath();
                }
                return implode(',', $result);
            }
        } catch (LocalizedException $e) {
        }
        return '';
    }

}
