<?php

declare (strict_types = 1);

namespace Branch8\MarketPlaceParentOrderFrontendUi\Block\ParentOrder;

use Magento\Store\Model\ScopeInterface as StoreScopeInterface;

/**
 * Sales parent order history block
 */
class CancelOrder extends \Magento\Framework\View\Element\Template

{
    private const SALES_CANCELLATION_REASONS = 'sales/cancellation/reasons';

    /**
     * @var \Branch8\MarketPlaceParentOrderFrontendUi\Model\Config
     */
    private $config;


    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Branch8\MarketPlaceParentOrderFrontendUi\Model\Config $config
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Branch8\MarketPlaceParentOrderFrontendUi\Model\Config $config,
        array $data = []
    ) {
        $this->config = $config;
        parent::__construct($context, $data);
    }

    /**
     * Returns order cancellation reasons.
     *
     * @return array
     */
    public function getReasons(): array
    {
        $reasons = $this->_scopeConfig->getValue(
            self::SALES_CANCELLATION_REASONS,
            StoreScopeInterface::SCOPE_STORE
        );
        return array_map(function ($reason) {
            return $reason['description'];
        }, is_array($reasons) ? $reasons : json_decode($reasons, true));
    }
}
