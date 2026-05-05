<?php

namespace Branch8\GA4\Block;

use Branch8\GA4\Model\Config;
use Branch8\GA4\Model\ProductHelper;
use Magento\Framework\View\Element\Template;

class Core extends \Magento\Framework\View\Element\Template
{
    private $config;

    private $storage;
    protected ProductHelper $productHelper;

    /**
     * @param Template\Context $context
     * @param Config $config
     * @param \Branch8\Ga4\Model\Storage $storage
     * @param array $data
     */
    public function __construct(
        Template\Context           $context,
        Config                     $config,
        \Branch8\GA4\Model\Storage $storage,
        ProductHelper              $productHelper,
        array                      $data = []
    )
    {
        $this->productHelper = $productHelper;
        $this->storage = $storage;
        $this->config = $config;
        parent::__construct($context, $data);
    }


    public function isAllowedPage()
    {
        $currentPath = $this->getRequest()->getPathInfo();
        $marketplaceRoutes = [
            '/marketplace',
            '/sellersubaccount',
            '/mprmasystem',
            '/mppreorder',
            '/mpmassupload',
            '/b8marketplace',
        ];

        foreach ($marketplaceRoutes as $route) {
            if (str_starts_with($currentPath, $route)) {
                return false;
            }
        }

        return true;
    }

    public function isEnabled()
    {
        return $this->config->isEnabled();
    }

    /**
     * @param $label
     * @param $value
     * @return $this
     */
    public function setDataLayerOption($label, $value)
    {
        $this->storage->setData($label, $value);
        return $this;
    }

    /**
     * @param $label
     * @return array|mixed|null/**
     *
     */
    public function getDataLayerOption($label = null)
    {
        if ($label) {
            return $this->storage->getData($label);
        }

        $storageData = $this->storage->getData();
        unset($storageData['additional_datalayer_option']);

        return $storageData;
    }

    /**
     * @return false|string
     */
    public function getDataLayerAsJson()
    {
        $options = $this->getDataLayerOption();
        return json_encode($options);
    }

    /**
     * @param $value
     * @return string
     */
    public function formatMoney($value)
    {
        return $this->productHelper->formatMoney($value);
    }

    public function getCurrencyCode()
    {
        return $this->_storeManager->getStore()->getCurrentCurrencyCode();
    }

    /**
     * @param $product
     * @param $code
     * @return string
     */
    protected function getLabelAttribute($product, $code)
    {
        $attribute = $product->getResource()->getAttribute($code);
        return $attribute ? $attribute->getFrontend()->getValue($product) : '';

    }
}
