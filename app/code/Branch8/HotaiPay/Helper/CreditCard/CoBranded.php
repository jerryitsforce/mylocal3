<?php

namespace Branch8\HotaiPay\Helper\CreditCard;

use Magento\Framework\App\Helper\AbstractHelper;

class CoBranded extends AbstractHelper
{

    protected $parentOrderPaymentCollection;

    protected $conn;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Branch8\HotaiPay\Model\ResourceModel\ParentOrderPayment\CollectionFactory $parentOrderPaymentCollection
    ){
        parent::__construct($context);
        $this->parentOrderPaymentCollection = $parentOrderPaymentCollection;
        $this->conn = $resourceConnection->getConnection();
    }

    public function getCoBrandedForChildOrderId($childId)
    {
        $query = $this->conn->select()
            ->from(['pc' => 'sales_parent_order_children'], ['parent_id'])
            ->where('children_id = ?', $childId)
            ->limit(1);
        $parentId = $this->conn->fetchOne($query);

        return $this->getCoBrandedForParentOrderId($parentId);
    }

    public function getCoBrandedForParentOrderId($parentId)
    {
        if(!$parentId){
            return '';
        }
        $coBrandedData = $this->parentOrderPaymentCollection->create()
            ->addFieldToFilter('parent_id', $parentId)
            ->addFieldToSelect('co_branded')
            ->getFirstItem();
        return $coBrandedData->getCoBranded();
    }



}