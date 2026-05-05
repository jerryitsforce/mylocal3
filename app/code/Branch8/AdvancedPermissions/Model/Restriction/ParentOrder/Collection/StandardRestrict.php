<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Model\Restriction\ParentOrder\Collection;

use Amasty\Rolepermissions\Helper\Data as Helper;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Branch8\AdvancedPermissions\Block\Adminhtml\Role\Tab\Seller;
use Branch8\MarketPlaceParentOrderAdminUi\Model\ResourceModel\ParentOrder\Grid\Collection as ParentOrderGridCollection;

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

    public function execute(ParentOrderGridCollection $collection): void
    {
        try {
            if (!in_array($this->appState->getAreaCode(), Helper::ALLOWED_AREA_CODES)) {
                return;
            }
        } catch (LocalizedException $e) {
            return;
        }

        $objectId = spl_object_hash($collection);

        if (isset($this->restrictedObjects[$objectId])) {
            return;
        }

        $rule = $this->helper->currentRule();
        if (is_object($rule)) {
            $this->restrictSellerCollection($rule, $collection);
            $this->restrictedObjects[$objectId] = true;
        }
    }

    public function restrictSellerCollection($rule, ParentOrderGridCollection $collection): void
    {
        switch ($rule->getSellerAccessMode()) {
            case Seller::MODE_ANY:
                break;
            case Seller::MODE_SELECTED:
                $collection->getSelect()->where('mo.seller_id IN (?)', $rule->getSellers() ?? []);
                break;
        }
    }
}
