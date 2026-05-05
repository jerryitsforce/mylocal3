<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\SplitCart\Override\Plugin\Magento\Checkout\Block\Cart;

/**
 * Class Grid
 * @package Branch8\SplitCart\Override\Plugin\Magento\Checkout\Block\Cart\Grid
 */
class Grid extends \Tigren\SplitCart\Plugin\Magento\Checkout\Block\Cart\Grid
{
    public function beforeToHtml(
        \Magento\Checkout\Block\Cart\Grid $subject
    ) {
        $enableSplitCart = $this->helper->isEnabledSplitCart();
        if ($enableSplitCart) {
            $subject->setTemplate('Branch8_SplitCart::cart/form.phtml');
        }
    }
}
