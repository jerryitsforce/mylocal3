<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Plugin\Catalog\Ui\DataProvider\Product\Form;

class ProductDataProvider
{
    /**
     * @var \Amasty\Rolepermissions\Helper\Data
     */
    private $helper;

    public function __construct(
        \Amasty\Rolepermissions\Helper\Data $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * @param \Magento\Catalog\Ui\DataProvider\Product\Form\ProductDataProvider $subject
     * @param $result
     * @return mixed
     */
    public function afterGetData(\Magento\Catalog\Ui\DataProvider\Product\Form\ProductDataProvider $subject, $result)
    {
        $allowedAttributeCodes = $this->helper->getAllowedAttributeCodes();
        if ($result) {
            $currentProduct = current($result);
            $productId = array_keys($result)[0];

            if (is_array($allowedAttributeCodes) && !in_array('quantity_and_stock_status', $allowedAttributeCodes)
                && isset($currentProduct['product']['quantity_and_stock_status'])) {
                unset($currentProduct['product']['quantity_and_stock_status']);
                $result[$productId] = $currentProduct;
            }
        }

        return $result;
    }
}
