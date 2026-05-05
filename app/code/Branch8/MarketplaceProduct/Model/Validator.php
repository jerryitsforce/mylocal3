<?php

namespace Branch8\MarketplaceProduct\Model;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\RequestInterface;

class Validator
{
    private array $instances;

    /**
     * @param array $instances
     */
    public function __construct(
        array $instances = []
    )
    {
        $this->instances = $instances;
    }

    /**
     * @param Product $product
     * @param RequestInterface $request
     * @param \Magento\Framework\DataObject $response
     * @return void
     */
    public function validate(Product $product, RequestInterface $request, \Magento\Framework\DataObject $response)
    {
        /**
         * @var $instance ValidatorInterface
         */
        foreach ($this->instances as $instance) {
            $instance->validate($product, $request, $response);
        }
    }
}
