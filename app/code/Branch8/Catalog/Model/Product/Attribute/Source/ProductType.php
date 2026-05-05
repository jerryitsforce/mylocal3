<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\Catalog\Model\Product\Attribute\Source;

use Magento\Catalog\Model\ProductTypes\ConfigInterface;

class ProductType extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{

    /**
     * Product type configuration provider
     *
     * @var ConfigInterface
     */
    private $productTypeConfig;

    public function __construct(
        ConfigInterface $productTypeConfig
    ){
        $this->productTypeConfig = $productTypeConfig;
    }
    /**
     * getAllOptions
     *
     * @return array
     */
    public function getAllOptions()
    {
        $this->_options = [];
        foreach ($this->productTypeConfig->getAll() as $productTypeData) {
            $this->_options[] = ['value' => $productTypeData['name'], 'label' => $productTypeData['label']];
        }
        return $this->_options;
    }
}