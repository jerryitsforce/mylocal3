<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Plugin\Catalog\Model\Product;

use Amasty\Rolepermissions\Block\Adminhtml\Role\Tab\Scope;
use Amasty\Rolepermissions\Exception\AccessDeniedException;
use Amasty\Rolepermissions\Helper\Data as Helper;
use Magento\Catalog\Model\Product;

class RestrictAccess
{
    /**
     * @var Helper
     */
    private $helper;

    public function __construct(
        Helper $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * @throws AccessDeniedException
     */
    public function afterLoad(Product $subject, Product $product)
    {
        $rule = $this->helper->currentRule();

        if ($rule
            && $rule->getScopeStoreviews()
        ) {
            $accessDenied = false;

            switch ($rule->getScopeAccessMode()) {
                case Scope::MODE_SITE:
                    $accessDenied = !array_intersect($rule->getScopeWebsites(), $product->getWebsiteIds());
                    break;
                case Scope::MODE_VIEW:
                    $accessDenied = !array_intersect($rule->getScopeStoreviews(), $product->getStoreIds());
                    break;
            }

            if ($accessDenied) {
                throw new AccessDeniedException(
                    __('Access to the product with ID %1 is denied', $product->getId())
                );
            }
        }
        return $product;
    }
}
