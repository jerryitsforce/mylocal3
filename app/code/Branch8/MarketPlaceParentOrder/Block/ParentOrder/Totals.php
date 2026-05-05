<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Block\ParentOrder;

use Branch8\MarketPlaceParentOrder\Api\ParentOrderRepositoryInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use  Magento\Framework\View\Element\Template\Context;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;

class Totals extends \Magento\Framework\View\Element\Template
{
    /**
     * @var ParentOrderManagementInterface
     */
    private $parentOrderManagement;
    /**
     * @var ParentOrderRepositoryInterface
     */
    private $parentOrderRepository;

    /**
     * @param Context $context
     * @param ParentOrderRepositoryInterface $parentOrderRepository
     * @param ParentOrderManagementInterface $parentOrderManagement
     * @param array $data
     */
    public function __construct(
        Context                        $context,
        ParentOrderRepositoryInterface $parentOrderRepository,
        ParentOrderManagementInterface $parentOrderManagement,
        array                          $data = []
    )
    {
        parent::__construct($context, $data);
        $this->parentOrderManagement = $parentOrderManagement;
        $this->parentOrderRepository = $parentOrderRepository;
    }

    /**
     * @return ParentOrder
     */
    public function getParentOrder()
    {
        $parentOrder = $this->getData('order');

        if ($parentOrder !== null) {
            return $parentOrder;
        }
        $orderId = (int)$this->getData('parent_order_id');
        if ($orderId) {
            $parentOrder = $this->parentOrderRepository->get($orderId);
            $this->setData('parent_order', $parentOrder);
        }

        return $this->getData('parent_order');
    }

    /**
     * @return array
     */
    public function getTotals()
    {
        return $this->parentOrderManagement->getTotals($this->getParentOrder());
    }

    /**
     * @param $total
     * @return string
     */
    public function formatValue($total)
    {
        if (!$total->getIsFormated()) {
            return $this->getParentOrder()->formatPrice($total->getValue());
        }
        return $total->getValue();
    }
}
