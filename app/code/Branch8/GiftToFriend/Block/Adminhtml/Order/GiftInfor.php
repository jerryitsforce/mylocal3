<?php
namespace Branch8\GiftToFriend\Block\Adminhtml\Order;

use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderRepositoryInterface;

class GiftInfor extends \Magento\Framework\View\Element\Template
{

    protected $_coreRegistry = null;

    protected $parentOrder;

    protected $parentOrderRepository;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        ParentOrder $parentOrder,
        ParentOrderRepositoryInterface $parentOrderRepository,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_coreRegistry = $registry;
        $this->parentOrder = $parentOrder;
        $this->parentOrderRepository = $parentOrderRepository;
    }

    public function getOrder()
    {
        if ($this->_coreRegistry->registry('current_order')) {
            return $this->_coreRegistry->registry('current_order');
        }else{
            return null;
        }
    }

    public function getParentOrder(){
        $subOrderId = $this->getOrder()->getId();
        $parentOrderId = $this->parentOrder->getParentOrder($subOrderId);
        if(empty($parentOrderId)) {
            return null;
        }

        $parentOrder = $this->parentOrderRepository->get((int)$parentOrderId);
        return $parentOrder;
    }
}
