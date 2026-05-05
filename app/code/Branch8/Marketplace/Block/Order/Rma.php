<?php

namespace Branch8\Marketplace\Block\Order;

use Branch8\Rma\Model\Actions\PrepareRmaDataForForm;
use \Branch8\Rma\Model\Actions\ReasonsByTags;
use Magento\Framework\Escaper;

class Rma extends \Magento\Framework\View\Element\Template{

    /** @var \Branch8\Rma\Model\Actions\PrepareRmaDataForForm $prepareRmaDataForForm */
    protected $prepareRmaDataForForm;

    /** @var \Magento\Sales\Api\OrderRepositoryInterface $_orderRepository */
    protected $_orderRepository;

    /** @var \Branch8\Rma\Model\Actions\ReasonsByTags $reasonsByTags */
    protected $reasonsByTags;
    
    /** @var \Magento\Framework\Escaper $escaper */
    private $escaper;

    public  function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Sales\Api\OrderRepositoryInterface $_orderRepository,
        PrepareRmaDataForForm $prepareRmaDataForForm,
        ReasonsByTags $reasonsByTags,
        Escaper                  $escaper
        
    ){
        parent::__construct($context);
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_orderRepository = $_orderRepository;
        $this->prepareRmaDataForForm = $prepareRmaDataForForm;
        $this->reasonsByTags = $reasonsByTags;
        $this->escaper = $escaper;
    }
    
    /**
     * getRmaData
     *
     * @param  int $orderId
     * @return \Branch8\Rma\Model\Actions\PrepareRmaDataForForm
     */
    public function getRmaData($orderId) {
        return $this->prepareRmaDataForForm->execute($orderId);
    } 
    
    /**
     * getOrder
     *
     * @param  int $id
     * @return \Magento\Sales\Api\OrderRepositoryInterface
     */
    public function getOrder($id){
        $order = $this->_orderRepository->get($id);
        return $order;
    }
    
    /**
     * getItems
     *
     * @param  int $orderId
     * @return array
     */
    public function getItems($orderId){
        $order = $this->getOrder($orderId);
        $orderItems = $order->getAllVisibleItems();
        $items = [];
        foreach($orderItems as $_orderItem){
            $items[$_orderItem->getId()] = $_orderItem;
        }
        return $items;
    }
    
    /**
     * getAllReasonsForSeller
     *
     * @return array
     */
    public function getAllReasonsForSeller()
    {
        $reasons = [];
        foreach ($this->reasonsByTags->find(ReasonsByTags::SELLER_TAG) as $row) {
            $reasons[$row['id']] = $this->escaper->escapeHtml($row['reason']);
        }
        return $reasons;
    }
}