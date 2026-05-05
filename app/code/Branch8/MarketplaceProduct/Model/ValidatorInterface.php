<?php

namespace Branch8\MarketplaceProduct\Model;

use Magento\Catalog\Model\Product;
use Magento\Framework\App\RequestInterface;

interface ValidatorInterface
{
    /**
     * @param Product $product
     * @param RequestInterface $request
     * @param \Magento\Framework\DataObject $response
     * @return mixed
     */
    public function validate(Product $product, RequestInterface $request, \Magento\Framework\DataObject $response);
}
