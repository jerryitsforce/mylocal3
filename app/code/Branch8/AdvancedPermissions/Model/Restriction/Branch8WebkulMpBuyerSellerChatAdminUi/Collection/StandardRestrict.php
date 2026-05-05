<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Model\Restriction\Branch8WebkulMpBuyerSellerChatAdminUi\Collection;

use Amasty\Rolepermissions\Helper\Data as Helper;
use Branch8\AdvancedPermissions\Block\Adminhtml\Role\Tab\Seller;
use Branch8\WebkulMpBuyerSellerChatAdminUi\Model\ResourceModel\Profiles\Grid\Collection as ChatProfileGridCollection;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;

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
        State  $appState
    )
    {
        $this->helper = $helper;
        $this->appState = $appState;
    }

    public function execute(ChatProfileGridCollection $collection): void
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

    public function restrictSellerCollection($rule, ChatProfileGridCollection $collection): void
    {
        switch ($rule->getSellerAccessMode()) {
            case Seller::MODE_ANY:
                break;
            case Seller::MODE_SELECTED:
                $collection->getSelect()->join('marketplace_userdata',
                    'main_table.object_id=marketplace_userdata.seller_id')
                    ->where('main_table.registered_as = ? ', 'seller');
                $collection->addFieldToFilter('marketplace_userdata.seller_id', ['in' => $rule->getSellers() ?? []]);
                break;
        }
    }
}
