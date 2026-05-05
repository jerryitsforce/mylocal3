<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Plugin\WebkulMarketplace\Model\ResourceModel\Product;

use Branch8\AdvancedPermissions\Model\Restriction\Product\Collection\RestrictInterface as CollectionRestrictInterface;
use Webkul\Marketplace\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Framework\App\RequestInterface;

class RestrictLoading
{
    private const API_MODULE_NAME = 'api';

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var CollectionRestrictInterface
     */
    private $productCollectionRestrict;

    public function __construct(
        RequestInterface $request,
        CollectionRestrictInterface $productCollectionRestrict
    ) {
        $this->request = $request;
        $this->productCollectionRestrict = $productCollectionRestrict;
    }

    public function beforeLoad(ProductCollection $subject): void
    {
        if ($this->request->getModuleName() === self::API_MODULE_NAME) {
            return;
        }

        $this->productCollectionRestrict->execute($subject);
    }
}
