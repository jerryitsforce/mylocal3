<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\PageBuilder\Block\Widget;

use Magento\Framework\View\Element\Template;
use Magento\Widget\Block\BlockInterface;
use Magento\Framework\View\Element\Template\Context;
use Branch8\Customer\Helper\Data as CustomerHelper;

class Memberbar extends Template implements BlockInterface
{
    /**
     * @var CustomerHelper
     */
    protected $customerHelper;

    /**
     * @param Context $context
     * @param CustomerHelper $customerHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        CustomerHelper $customerHelper,
        array $data = []
    ) {
        $this->customerHelper = $customerHelper;
        parent::__construct($context, $data);
    }

    protected $_template = "widget/memberbar.phtml";

    /**
     * Check if customer is logged in and is a regular buyer (not seller/sub-account)
     *
     * @return bool
     */
    public function isLoggedInAndIsBuyer(): bool
    {
        return $this->customerHelper->isLoggedInAndIsBuyer();
    }
}
