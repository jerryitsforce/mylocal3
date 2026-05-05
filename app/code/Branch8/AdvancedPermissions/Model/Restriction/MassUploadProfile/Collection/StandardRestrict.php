<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Model\Restriction\MassUploadProfile\Collection;

use Amasty\Rolepermissions\Helper\Data as Helper;
use Webkul\MpMassUpload\Model\ResourceModel\Profile\Collection as ProfileCollection;
use Webkul\MpMassUpload\Model\ResourceModel\Profile\Grid\Collection as ProfileGridCollection;
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

    public function execute(ProfileCollection|ProfileGridCollection $profileCollection): void
    {
        try {
            if (!in_array($this->appState->getAreaCode(), Helper::ALLOWED_AREA_CODES)) {
                return;
            }
        } catch (LocalizedException $e) {
            return;
        }

        $objectId = spl_object_hash($profileCollection);

        if (isset($this->restrictedObjects[$objectId])) {
            return;
        }

        $rule = $this->helper->currentRule();
        if (is_object($rule)) {
            $this->restrictProfileCollection($rule, $profileCollection);
            $this->restrictedObjects[$objectId] = true;
        }
    }

    public function restrictProfileCollection($rule, ProfileCollection|ProfileGridCollection $collection): void
    {
        switch ($rule->getSellerAccessMode()) {
            case Seller::MODE_ANY:
                break;
            case Seller::MODE_SELECTED:
                if ($rule->getSellers()) {
                    $collection->addFieldToFilter('customer_id', ['in' => $rule->getSellers()]);
                }
                break;
        }
    }
}
