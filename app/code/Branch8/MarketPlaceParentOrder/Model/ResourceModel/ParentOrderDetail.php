<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\ResourceModel;

use Magento\SalesSequence\Model\Manager;
use Magento\Sales\Model\EntityInterface;
use Magento\Store\Model\Store;

class ParentOrderDetail extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var Manager
     */
    private $sequenceManager;

    /**
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param Manager $sequenceManager
     * @param string|null $connectionName
     */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        Manager                                   $sequenceManager,
        string                                            $connectionName = null
    )
    {
        $this->sequenceManager = $sequenceManager;
        parent::__construct($context, $connectionName);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('sales_parent_order_detail', 'entity_id');
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this|ParentOrderDetail
     */
    protected function _beforeSave(\Magento\Framework\Model\AbstractModel $object)
    {
        /**
         * @var $object \Branch8\MarketPlaceParentOrder\Model\ParentOrderDetail
         */
        if ($object->getEntityId() === null && $object->getIncrementId() === null) {
            /**
             * @var Store
             */
            $store = $object->getStore();
            $storeId = $store->getId();
            if ($storeId === null) {
                $storeId = $store->getGroup()->getDefaultStoreId();
            }
            $object->setIncrementId(
                $this->sequenceManager->getSequence(
                    $object->getEntityType(),
                    $storeId
                )->getNextValue()
            );
        }
        parent::_beforeSave($object);
        return $this;
    }

}
