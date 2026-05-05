<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Api\Data;
interface ParentOrderInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    const INDEX_ID = 'index_id';

    const ORDER_IDS = 'order_ids';
    const LAST_ORDER_ID = 'last_order_id';
    const PAYMENT_STATUS = 'payment_status';

    /**
     * @return int
     */
    public function getEntityId();

    /**
     * @param int $value
     * @return ParentOrderInterface
     */
    public function setEntityId(int $value);

    /**
     * @return int
     */
    public function getIndexId();

    /**
     * @param int $value
     * @return ParentOrderInterface
     */
    public function setIndexId(int $value);

    /**
     * @return string
     */
    public function getOrderIds();

    /**
     * @param string $value
     * @return ParentOrderInterface
     */
    public function setOrderIds(string $value);

    /**
     * @return int
     */
    public function getPaymentStatus();

    /**
     * @param int $value
     * @return ParentOrderInterface
     */
    public function setPaymentStatus(int $value);

    /**
     * @return int
     */
    public function getLastOrderId();

    /**
     * @param int $value
     * @return ParentOrderInterface
     */
    public function setLastOrderId(int $value);

    /**
     * @return \Magento\Sales\Api\Data\OrderInterface[]
     */
    public function getSubOrders();

    /**
     * @param array $suborders
     * @return ParentOrderInterface
     */
    public function setSubOrders(array $suborders = []);

    /**
     * @return ParentOrderDetailInterface
     */
    public function getDetail();

    /**
     * @param ParentOrderDetailInterface $detail
     * @return ParentOrderInterface
     */
    public function setDetail(ParentOrderDetailInterface $detail);

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderExtensionInterface $extensionAttributes
    );
}
