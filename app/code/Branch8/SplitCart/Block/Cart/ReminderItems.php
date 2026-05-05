<?php
namespace Branch8\SplitCart\Block\Cart;

use Magento\Framework\View\Element\Template\Context;

/**
 * Class ReminderItems
 */
class ReminderItems extends \Magento\Framework\View\Element\Template
{
    /**
     *
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
    }

    public function getHotaiGoTravelUrl() {
        return $this->_urlBuilder->getUrl('account/hotaigotravel');
    }
}
