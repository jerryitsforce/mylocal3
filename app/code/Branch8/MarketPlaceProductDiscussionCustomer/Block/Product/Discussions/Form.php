<?php
declare(strict_types=1);
/**
 * @project    HotaiConnected
 * @package    Branch8
 * @author     cuongho803@gmail.com
 * @date       27/02/2026
 */

namespace Branch8\MarketPlaceProductDiscussionCustomer\Block\Product\Discussions;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
class Form extends \Magento\Framework\View\Element\Template
{
    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Template\Context $context, array $data = []
    )
    {
        parent::__construct($context, $data);
    }
}
