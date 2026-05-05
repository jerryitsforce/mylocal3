<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\SplitCart\Override\Plugin\Magento\Checkout\Block\Cart\Item;

class Renderer extends \Tigren\SplitCart\Plugin\Magento\Checkout\Block\Cart\Item\Renderer
{
    /**
     * @param \Magento\Checkout\Block\Cart\Item\Renderer $subject
     */
    public function beforeToHtml(
        \Magento\Checkout\Block\Cart\Item\Renderer $subject
    ) {
        $enableSplitCart = $this->helper->isEnabledSplitCart();
        if ($enableSplitCart) {
            $subject->setTemplate('Branch8_SplitCart::cart/item/default.phtml');
        }
    }
}