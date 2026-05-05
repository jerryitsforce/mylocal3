<?php

namespace Branch8\AdvancedPermissions\Plugin\WebkulRmaSystem\Model\Details;

use Amasty\Rolepermissions\Helper\Data as Helper;
use Branch8\AdvancedPermissions\Block\Adminhtml\Role\Tab\Seller;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Webkul\MpRmaSystem\Model\Details;

class RestrictAccess
{
    protected $checkedIds = [];
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


    /**
     * @param Details $subject
     * @param Details $result
     * @param int|null $id
     * @param string|null $field
     * @return Details
     */
    public function afterLoad(Details $subject, Details $result, $id, $field = null): Details
    {
        if (in_array($result->getId(), $this->checkedIds)) {
            return $result;
        }
        try {
            if (!in_array($this->appState->getAreaCode(), Helper::ALLOWED_AREA_CODES)) {
                return $result;
            }
        } catch (LocalizedException $e) {
            return $result;
        }


        $rule = $this->helper->currentRule();
        if (is_object($rule) && Seller::MODE_SELECTED == $rule->getSellerAccessMode()) {
            $ruleSellers = $rule->getSellers() ?? [];

            if (!in_array($result->getSellerId(), $ruleSellers)) {
                $this->checkedIds[] = $result->getId();
                throw new LocalizedException(__('You are not allowed to access this RMA.'));
            }
        }

        $this->checkedIds[] = $result->getId();
        return $result;
    }

}
