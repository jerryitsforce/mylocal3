<?php

namespace Branch8\GA4\Model;
class Storage extends \Magento\Framework\Model\AbstractModel
{
    private $events = [];

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry      $registry
    )
    {
        parent::__construct($context, $registry);
    }

    public function setEventName($eventName, $params)
    {

    }
}
