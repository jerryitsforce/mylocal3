<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2023 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Branch8\TigrenSplitCart\Plugin\Magento\Rule\Model\Condition;

use Magento\Framework\App\Area;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\State;
use Magento\Framework\Model\AbstractModel;
use Magento\Quote\Model\Quote\Item;
use Magento\Rule\Model\Condition\Combine;

/**
 * Class AbstractCondition
 * @package Tigren\SplitCart\Plugin\Magento\Rule\Model\Condition
 */
class AbstractCondition
{
    private $state;

    /**
     * @return mixed
     */
    private function getState()
    {
        if ($this->state === null) {
            $this->state = ObjectManager::getInstance()->get(State::class);
        }
        return $this->state;
    }

    /**
     * @param Combine $subject
     * @param AbstractModel $model
     * @param $result
     * @return false|mixed
     */
    public function afterValidate(
        \Magento\Rule\Model\Condition\AbstractCondition $subject,
        $result,
        AbstractModel $model
    ) {
        $isAdmin = $this->getState()->getAreaCode() === Area::AREA_ADMINHTML;

        if ($result && $model instanceof Item && !$model->getAvailableToCheckout() && !$isAdmin) {
            return false;
        }

        return $result;
    }
}
