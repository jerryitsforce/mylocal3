<?php
/**
 * @package
 * @author      Cuong Ho <cuonghh@forixwebdesign.com>
 * @copyright   Copyright © 2021 Forix LLC. All Rights Reserved. *
 */
declare(strict_types=1);

namespace Branch8\GA4\ViewModel;

use Magento\Framework\Registry;

class ProductViewModel implements \Magento\Framework\View\Element\Block\ArgumentInterface
{
    private Registry $registry;

    public function __construct(
        Registry $registry
    ) {
        $this->registry = $registry;
    }

    public function getProduct()
    {
        return $this->registry->registry('current_product');
    }
}
