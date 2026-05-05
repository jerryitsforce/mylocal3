<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Block\ParentOrder\Email;

use Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderRepositoryInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\View\Element\Template\Context;

/**
 *
 */
class Items extends \Magento\Sales\Block\Items\AbstractItems
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    private ParentOrderFactory $parentOrderFactory;

    /**
     * @param Context $context
     * @param array $data
     * @param ParentOrderRepositoryInterface|null $orderRepository
     */
    public function __construct(
        Context                         $context,
        ParentOrderFactory              $parentOrderFactory,
        array                           $data = [],
    )
    {
        $this->parentOrderFactory = $parentOrderFactory;
        parent::__construct($context, $data);
    }

    /**
     * Returns order.
     *
     * Custom email templates are only allowed to use scalar values for variable data.
     * So order is loaded by order_id, that is passed to block from email template.
     * For legacy custom email templates it can pass as an object.
     *
     * @return ParentOrderInterface
     * @since 102.1.0
     */
    public function getParentOrder()
    {
        $order = $this->getData('parent_order');

        if ($order !== null) {
            return $order;
        }
        $orderId = (int)$this->getData('parent_order_id');
        if ($orderId) {
            //app/code/Branch8/MarketPlaceParentOrder/Model/Services/ProcessParentOrdersWhenCheckoutWithFullPoint.php make $order->getSubOrders() return empty array
            //so i use $this->parentOrderFactory->create()->load($orderId) instead of $this->orderRepository->get($orderId);
            //TODO: in future, we should use $this->orderRepository->get($orderId) instead of $this->parentOrderFactory->create()->load($orderId)
            $order = $this->parentOrderFactory->create()->load($orderId);
            $this->setData('parent_order', $order);
        }

        return $this->getData('parent_order');
    }
}
