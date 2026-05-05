<?php

declare(strict_types=1);

namespace Branch8\OneStepCheckout\Block\Onepage\Success;

use Magento\Checkout\Model\Session;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;

class Details extends Template
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var Session
     */
    protected $session;

    public function __construct(
        Template\Context $context,
        Registry $registry,
        Session $session,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->session = $session;
    }

    /**
     * clear custom session
     *
     * @return bool
     */
    public function clearCustomSession()
    {
        $this->session->unsReferrerCode();
        $this->session->unsOrderNote();
        return true;
    }
}
