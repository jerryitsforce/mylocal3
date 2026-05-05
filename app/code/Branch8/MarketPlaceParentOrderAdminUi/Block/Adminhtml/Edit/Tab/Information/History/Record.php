<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderAdminUi\Block\Adminhtml\Edit\Tab\Information\History;

use Branch8\MarketPlaceParentOrder\Model\ParentOrder;

class Record extends \Magento\Backend\Block\Template
{
    protected $_template='Branch8_MarketPlaceParentOrderAdminUi::tab/view/information/history/record.phtml';
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry             $registry,
        array                                   $data = []
    )
    {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * @param \Magento\Sales\Model\Order\Status\History $history
     * @return bool
     */
    public function isCustomerNotificationNotApplicable(\Branch8\MarketPlaceParentOrder\Model\History $history)
    {
        return $history->isCustomerNotificationNotApplicable();
    }
}
