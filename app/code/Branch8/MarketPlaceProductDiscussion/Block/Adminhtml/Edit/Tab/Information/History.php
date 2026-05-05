<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Block\Adminhtml\Edit\Tab\Information;

use Branch8\MarketPlaceParentOrder\Model\ParentOrder;

class History extends \Magento\Backend\Block\Template
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;
    /**
     * @var \Branch8\MarketPlaceParentOrderAdminUi\Model\Config
     */
    private $config;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Branch8\MarketPlaceParentOrderAdminUi\Model\Config $config
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context             $context,
        \Magento\Framework\Registry                         $registry,
        \Branch8\MarketPlaceParentOrderAdminUi\Model\Config $config,
        array                                               $data = []
    )
    {
        $this->_coreRegistry = $registry;
        $this->config = $config;
        parent::__construct($context, $data);
    }

    /**
     * @return ParentOrder
     */
    public function getParentOrder()
    {
        return $this->_coreRegistry->registry('parent_order');
    }

    /**
     * @return \Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderDetailInterface|null
     */
    public function getDetail()
    {
        return $this->getParentOrder()->getExtensionAttributes()->getDetail();
    }

    /**
     * @return array
     */
    public function getStatuses()
    {
        $state = $this->getParentOrder()->getDetail()->getState();
        $statuses = ['' => __('Please Select')];
        $parentOrder = $this->getParentOrder();
        $statuses = array_merge($statuses, $parentOrder->getConfig()->getStatuses());
        return $statuses;
    }

    /**
     * @return void
     */
    public function getStatusHistoryCollection()
    {
        return $this->getParentOrder()->getHistory();
    }

    /**
     * @return bool
     */
    public function showStatus()
    {
        return $this->config->showStatus();
    }
}
