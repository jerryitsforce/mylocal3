<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Model\Restriction\ProductVersion\Collection;

use Amasty\Rolepermissions\Helper\Data as Helper;
use Branch8\MarketplaceProduct\Model\ResourceModel\ProductVersion\Collection as ProductVersionCollection;
use Branch8\MarketplaceProduct\Model\ResourceModel\ProductVersion\Grid\Collection as ProductVersionGridCollection;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Branch8\AdvancedPermissions\Block\Adminhtml\Role\Tab\Seller;

class StandardRestrict implements RestrictInterface
{
    /**
     * @var array
     */
    private $restrictedObjects = [];

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var State
     */
    private $appState;

    public function __construct(
        Helper $helper,
        State $appState
    ) {
        $this->helper = $helper;
        $this->appState = $appState;
    }

    public function execute(ProductVersionCollection|ProductVersionGridCollection $productVersionCollection): void
    {
        try {
            if (!in_array($this->appState->getAreaCode(), Helper::ALLOWED_AREA_CODES)) {
                return;
            }
        } catch (LocalizedException $e) {
            return;
        }

        $objectId = spl_object_hash($productVersionCollection);

        if (isset($this->restrictedObjects[$objectId])) {
            return;
        }

        $rule = $this->helper->currentRule();
        if (is_object($rule)) {
            $this->restrictProductVersionCollection($rule, $productVersionCollection);
            $this->restrictedObjects[$objectId] = true;
        }
    }

    public function restrictProductVersionCollection($rule, ProductVersionCollection|ProductVersionGridCollection $collection): void
    {
        switch ($rule->getSellerAccessMode()) {
            case Seller::MODE_ANY:
                break;
            case Seller::MODE_SELECTED:
                if ($rule->getSellers()) {
                    $collection->getSelect()->join(
                        ['mp' => 'marketplace_product'],
                        'main_table.product_id = mp.mageproduct_id',
                        []
                    );
                    $collection->addFieldToFilter('mp.seller_id', ['in' => $rule->getSellers()]);
                    $collection->getSelect()->group('main_table.product_id');
                }
                break;
        }
    }
}
