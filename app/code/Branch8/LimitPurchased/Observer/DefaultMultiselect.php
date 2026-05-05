<?php

namespace Branch8\LimitPurchased\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Eav\Model\Config as EavConfig;

class DefaultMultiselect implements ObserverInterface
{
    /**
     * @var EavConfig
     */
    protected $eavConfig;

    public function __construct(EavConfig $eavConfig)
    {
        $this->eavConfig = $eavConfig;
    }

    public function execute(Observer $observer)
    {
        /** @var \Magento\Catalog\Model\Product $product */
        $product = $observer->getEvent()->getProduct();

        $attributeCode = 'limit_purchased_customer_group';

        // If already set, don’t override
        if ($product->getData($attributeCode)) {
            return;
        }

        // Get all option values
        $attribute = $this->eavConfig->getAttribute('catalog_product', $attributeCode);
        $options = $attribute->getSource()->getAllOptions(false);

        if (!empty($options)) {
            $values = array_column($options, 'value');
            $product->setData($attributeCode, implode(',', $values));
        }
    }
}