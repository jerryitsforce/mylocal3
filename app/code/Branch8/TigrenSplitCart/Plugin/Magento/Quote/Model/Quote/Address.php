<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2023 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Branch8\TigrenSplitCart\Plugin\Magento\Quote\Model\Quote;

use Closure;
use Magento\Framework\App\Area;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\State;
use Magento\Quote\Model\Quote;
use Tigren\SplitCart\Helper\Data;

/**
 * Class Address
 * @package Branch8\TigrenSplitCart\Plugin\Magento\Quote\Model\Quote
 */
class Address
{
    private $state;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @param Data $data
     */
    public function __construct(Data $data)
    {
        $this->helper = $data;
    }

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
     * @param Quote\Address $subject
     * @param Closure $proceed
     * @return array
     */
    public function aroundGetAllItems(
        Quote\Address $subject,
        Closure $proceed
    ) {

        $isAdmin = $this->getState()->getAreaCode() === Area::AREA_ADMINHTML;

        $enableSplitCart = $this->helper->isEnabledSplitCart();
        $items = $proceed();

        if ($subject->getQuote()->getIsUpdatingQty() || !$enableSplitCart) {
            return $items;
        }

        $availableItems = [];
        foreach ($items as $item) {
            if ($isAdmin || !$item->getId() || $item->getAvailableToCheckout() ) {
                $availableItems[] = $item;
            }
        }

        return $availableItems;
    }
}
