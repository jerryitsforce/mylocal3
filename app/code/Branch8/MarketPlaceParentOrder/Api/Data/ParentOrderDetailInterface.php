<?php

namespace Branch8\MarketPlaceParentOrder\Api\Data;

use Branch8\MarketPlaceParentOrder\Model\ParentOrderDetail;

interface ParentOrderDetailInterface
{
    const PARENT_ID = 'parent_id';

    const STATE = 'state';

    const STATUS = 'status';

    const INCREMENT_ID = 'increment_id';

    const CUSTOMER_ID = 'customer_id';
    const CUSTOMER_EMAIl = 'customer_email';
    const CUSTOMER_NAME = 'customer_name';

    const STORE_ID = 'store_id';
    const REMOTE_IP = 'remote_ip';
    const STORE_NAME = 'store_name';

    const CREATED_AT = 'created_at';

    const GROUP_ID = 'customer_group_id';
    const SHIPPING_DESCRIPTION = 'shipping_description';

    const PAYMENT_METHOD = 'payment_method';

    const ORDER_CURRENCY_CODE = 'order_currency_code';

    const CUSTOMER_IS_GUEST = 'customer_is_guest';

    const CUSTOMER_NOTE = 'customer_note';

    const SHIPPING_METHOD = 'shipping_method';

    const PARENT_RELATION_NEW_ID = 'parent_relation_new_id';

    const PARENT_RELATION_NEW_REAL_ID = 'parent_relation_new_real_id';

    const ORDER_NOTE = 'order_note';

    const REFERER_CODE = 'referrer_code';

    const ECPAY_INVOICE_CARRUER_NUM = 'ecpay_invoice_carruer_num';

    const ECPAY_INVOICE_CARRUER_TYPE = 'ecpay_invoice_carruer_type';

    const ECPAY_INVOICE_CUSTOMER_COMPANY = 'ecpay_invoice_customer_company';

    const ECPAY_INVOICE_CUSTOMER_IDENTIFIER = 'ecpay_invoice_customer_identifier';

    const RMA_STATUS = 'rma_status';


    /**
     * @return int
     */
    public function getParentId();

    /**
     * @param int $value
     * @return ParentOrderDetailInterface
     */
    public function setParentId(int $value);

    /**
     * @return string
     */
    public function getState();

    /**
     * @param string $value
     * @return ParentOrderDetailInterface
     */
    public function setState(string $value);

    /**
     * @return string
     */
    public function getStatus();

    /**
     * @param string $value
     * @return ParentOrderDetailInterface
     */
    public function setStatus(string $value);

    /**
     * @return string
     */
    public function getIncrementId();

    /**
     * @param string $value
     * @return ParentOrderDetailInterface
     */
    public function setIncrementId(string $value);

    /**
     * @return int
     */
    public function getCustomerId();

    /**
     * @param int $value
     * @return ParentOrderDetailInterface
     */
    public function setCustomerId(int $value);

    /**
     * @return string
     */
    public function getCustomerEmail();

    /**
     * @param string $value
     * @return ParentOrderDetailInterface
     */
    public function setCustomerEmail(string $value);

    /**
     * @return string
     */
    public function getCustomerName();

    /**
     * @param string $value
     * @return ParentOrderDetailInterface
     */
    public function setCustomerName(string $value);

    /**
     * @return int
     */
    public function getStoreId();

    /**
     * @param int $value
     * @return ParentOrderDetailInterface
     */
    public function setStoreId(int $value);

    /**
     * @return string
     */
    public function getRemoteIp();

    /**
     * @param string $value
     * @return ParentOrderDetailInterface
     */
    public function setRemoteIp(int $value);

    /**
     * @return string
     */
    public function getStoreName();

    /**
     * @param string $value
     * @return ParentOrderDetailInterface
     */
    public function setStoreName(int $value);

    /**
     * @return string
     */
    public function getCreatedAt();

    /**
     * @param string $value
     * @return ParentOrderDetailInterface
     */
    public function setCreatedAt(string $value);

    /**
     * @return int
     */
    public function getCustomerGroupId();

    /**
     * @param int $value
     * @return ParentOrderDetail
     */
    public function setCustomerGroupId(int $value);

    /**
     * @return string
     */
    public function getShippingDescription();

    /**
     * @param string $value
     * @return ParentOrderDetail
     */
    public function setShippingDescription(int $value);

    /**
     * @return string
     */
    public function getPaymentMethod();

    /**
     * @param string $value
     * @return ParentOrderDetail
     */
    public function setPaymentMethod(string $value);

    /**
     * @return string
     */
    public function getOrderCurrencyCode();

    /**
     * @param string $value
     * @return ParentOrderDetail
     */
    public function setOrderCurrencyCode(string $value);

    /**
     * @return string
     */
    public function getCustomerIsGuest();

    /**
     * @param int $value
     * @return ParentOrderDetail
     */
    public function setCustomerIsGuest(int $value);

    /**
     * @return string
     */
    public function getShippingMethod();

    /**
     * @param string $value
     * @return ParentOrderDetail
     */
    public function setShippingMethod(string $value);

    /**
     * @return int
     */
    public function getParentRelationNewId();

    /**
     * @param int $value
     * @return ParentOrderDetail
     */
    public function setParentRelationNewId(int $value);

    /**
     * @return string
     */
    public function getParentRelationNewRealId();

    /**
     * @param string $value
     * @return ParentOrderDetail
     */
    public function setParentRelationNewRealId(string $value);
    /**
     * @return string
     */
    public function getOrderNote();

    /**
     * @param string $value
     * @return ParentOrderDetail
     */
    public function setOrderNote(string $value);
    /**
     * @return string
     */
    public function getReferrerCode();

    /**
     * @param string $value
     * @return ParentOrderDetail
     */
    public function setReferrerCode(string $value);

    /**
     * @return string
     */
    public function getEcpayInvoiceCarruerNum();

    /**
     * @param string $value
     * @return ParentOrderDetail
     */
    public function setEcpayInvoiceCarruerNum(string $value);

    /**
     * @return string
     */
    public function getEcpayInvoiceCarruerType();

    /**
     * @param string $value
     * @return ParentOrderDetail
     */
    public function setEcpayInvoiceCarruerType(string $value);

    /**
     * @return string
     */
    public function getEcpayInvoiceCustomerCompany();

    /**
     * @param string $value
     * @return ParentOrderDetail
     */
    public function setEcpayInvoiceCustomerCompany(string $value);

    /**
     * @return string
     */
    public function getEcpayInvoiceCustomerIdentifier();

    /**
     * @param string $value
     * @return ParentOrderDetail
     */
    public function setEcpayInvoiceCustomerIdentifier(string $value);

    /**
     * @return string
     */
    public function getRmaStatus();

    /**
     * @param string $value
     * @return ParentOrderDetail
     */
    public function setRmaStatus(string $value);
}
