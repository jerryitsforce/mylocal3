<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\Mmegamenu\Helper;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Context;

/**
 * Contact base helper
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @param \Magento\Framework\Registry $registry
     */
    protected $_registry;

    /**
     * @param Registry $registry
     * @param Context $context
     */
    public function __construct(
        Registry $registry,
        Context $context
    ) {
        $this->_registry = $registry;
        $this->scopeConfig = $context->getScopeConfig();
    }

    /**
     * Get store config
     * @param $node
     * @return mixed
     */
    public function getStoreConfig ($node)
    {
        return $this->scopeConfig->getValue($node, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get current category id
     * @return mixed
     */
    public function getCurentCateId()
    {
        return $this->_registry->registry('current_category');
    }
}
