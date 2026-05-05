<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderFrontendUi\Block\ParentOrder\Info;

use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Magento\Customer\Model\Context;

/**
 * @api
 * @since 100.0.2
 */
class Buttons extends \Magento\Framework\View\Element\Template
{
    /**
     * @var string
     */
    protected $_template = 'Branch8_MarketPlaceParentOrderFrontendUi::parent_order/info/buttons.phtml';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;
    /**
     * @var ParentOrderManagementInterface
     */
    private $parentOrderManagement;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param ParentOrderManagementInterface $parentOrderManagement
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry                      $registry,
        \Magento\Framework\App\Http\Context              $httpContext,
        ParentOrderManagementInterface                   $parentOrderManagement,
        array                                            $data = []
    )
    {
        $this->_coreRegistry = $registry;
        $this->httpContext = $httpContext;
        parent::__construct($context, $data);
        $this->parentOrderManagement = $parentOrderManagement;
    }

    /**
     * Retrieve current order model instance
     *
     * @return ParentOrder
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_parent_order');
    }

    /**
     * @param ParentOrder $order
     * @return string
     */
    public function getPrintUrl($order)
    {
        if (!$this->httpContext->getValue(Context::CONTEXT_AUTH)) {
            return $this->getUrl('sales/parentOrderGuest/print', ['id' => $order->getId()]);
        }
        return $this->getUrl('sales/parentOrder/print', ['id' => $order->getId()]);
    }

    /**
     * Get url for reorder action
     *
     * @param ParentOrder $order
     * @return string
     */
    public function getReorderUrl($order)
    {
        if (!$this->httpContext->getValue(Context::CONTEXT_AUTH)) {
            return $this->getUrl('sales/parentOrderGuest/parentOrderReorder', ['id' => $order->getId()]);
        }
        return $this->getUrl('sales/parentOrder/reorder', ['id' => $order->getId()]);
    }

    /**
     * @param ParentOrder $parentOrder
     * @return bool
     */
    public function canReorder(ParentOrder $parentOrder)
    {
        return (bool)$this->parentOrderManagement->canReorder($parentOrder);
    }
}
