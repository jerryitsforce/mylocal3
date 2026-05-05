<?php

declare(strict_types=1);

namespace Branch8\AdvancedPermissions\Model\Restriction\Branch8WebkulMpBuyerSellerChatAdminUi\ChatHistory\Collection;

use Amasty\Rolepermissions\Helper\Data as Helper;
use Branch8\AdvancedPermissions\Block\Adminhtml\Role\Tab\Seller;
use Branch8\WebkulMpBuyerSellerChatAdminUi\Model\ResourceModel\Message\Grid\Collection as ChatHistoryGridCollection;
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

    public function execute(ChatHistoryGridCollection $collection): void
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

    public function restrictSellerCollection($rule, ChatHistoryGridCollection $collection): void
    {
        switch ($rule->getSellerAccessMode()) {
            case Seller::MODE_ANY:
                break;
            case Seller::MODE_SELECTED:
                $collection->getSelect()
                    ->joinLeft(
                        ['cpf' => 'marketplace_chat_profile_info'],
                        'main_table.sender_unique_id = cpf.unique_id or main_table.receiver_unique_id = cpf.unique_id',
                        []
                    )
                    ->where('cpf.registered_as = ? ', 'seller');
                $collection->addFieldToFilter('cpf.object_id', ['in' => $rule->getSellers() ?? []]);
                break;
        }
    }
}
