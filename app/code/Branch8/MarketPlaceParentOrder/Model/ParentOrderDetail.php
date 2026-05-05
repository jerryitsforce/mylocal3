<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model;

use Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderDetailInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Model\AbstractModel;
use Magento\Sales\Model\Order\StatusLabel;
use Magento\Store\Model\StoreManagerInterface;

class ParentOrderDetail extends AbstractModel implements ParentOrderDetailInterface
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'sales_parent_order_detail';
    /**
     * @var string
     */
    protected $_eventObject = 'sales_parent_order_detail';
    /**
     * @var
     */
    private $entityType = 'parent_order';
    /**
     * @var StoreManagerInterface
     */
    private $_storeManager;
    /**
     * @var
     */
    private $customer = null;
    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param StoreManagerInterface $storeManager
     * @param CustomerFactory $customerFactory
     * @param StatusLabel|null $statusLabel
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context                        $context,
        \Magento\Framework\Registry                             $registry,
        StoreManagerInterface                                   $storeManager,
        CustomerFactory                                         $customerFactory,
        StatusLabel                                             $statusLabel = null,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb           $resourceCollection = null,
        array                                                   $data = []
    )
    {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->_storeManager = $storeManager;
        $this->customerFactory = $customerFactory;
        $this->statusLabel = $statusLabel ?: ObjectManager::getInstance()->get(StatusLabel::class);

    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrderDetail::class
        );
    }

    /**
     * @return array|int|mixed|null
     */
    public function getParentId()
    {
        return $this->getData(self::PARENT_ID);
    }

    /**
     * @param int $value
     * @return $this|ParentOrderDetailInterface
     */
    public function setParentId(int $value)
    {
        $this->setData(self::PARENT_ID, $value);
        return $this;
    }

    /**
     * @return array|mixed|string|null
     */
    public function getState()
    {
        return $this->getData(self::STATE);
    }

    /**
     * @param string $value
     * @return $this|ParentOrderDetailInterface
     */
    public function setState(string $value)
    {
        $this->setData(self::STATE, $value);
        return $this;
    }

    /**
     * @return array|mixed|string|null
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @param string $value
     * @return $this|ParentOrderDetailInterface
     */
    public function setStatus(string $value)
    {
        $this->setData(self::STATUS, $value);
        return $this;
    }

    /**
     * @return array|mixed|string|null
     */
    public function getIncrementId()
    {
        return $this->getData(self::INCREMENT_ID);
    }

    /**
     * @param string $value
     * @return $this|ParentOrderDetailInterface
     */
    public function setIncrementId(string $value)
    {
        $this->setData(self::INCREMENT_ID, $value);
        return $this;
    }

    /**
     * @return array|int|mixed|null
     */
    public function getCustomerId()
    {
        return $this->getData(self::CUSTOMER_ID);
    }

    /**
     * @return \Magento\Customer\Model\Customer
     */
    public function getCustomer()
    {
        if ($this->customer === null) {
            $this->customer = $this->customerFactory->create()->load(
                $this->getCustomerId()
            );
        }
        return $this->customer;
    }

    /**
     * @param int $value
     * @return $this|ParentOrderDetailInterface
     */
    public function setCustomerId(int $value)
    {
        $this->setData(self::CUSTOMER_ID, $value);
        return $this;
    }

    /**
     * @return array|mixed|string|null
     */
    public function getCustomerEmail()
    {
        return $this->getData(self::CUSTOMER_EMAIl);
    }

    /**
     * @param string $value
     * @return $this|ParentOrderDetailInterface
     */
    public function setCustomerEmail(string $value)
    {
        $this->setData(self::CUSTOMER_EMAIl, $value);
        return $this;
    }

    /**
     * @return array|mixed|string|null
     */
    public function getCustomerName()
    {
        return $this->getData(self::CUSTOMER_NAME);
    }

    /**
     * @param string $value
     * @return $this|ParentOrderDetailInterface
     */
    public function setCustomerName(string $value)
    {
        $this->setData(self::CUSTOMER_NAME, $value);
        return $this;
    }

    /**
     * @return array|int|mixed|null
     */
    public function getStoreId()
    {
        return $this->getData(self::STORE_ID);
    }

    /**
     * @param int $value
     * @return $this|ParentOrderDetailInterface
     */
    public function setStoreId(int $value)
    {
        $this->setData(self::STORE_ID, $value);
        return $this;
    }

    /**
     * @return array|mixed|string|null
     */
    public function getRemoteIp()
    {
        return $this->getData(self::REMOTE_IP);
    }

    /**
     * @param int $value
     * @return $this|ParentOrderDetailInterface
     */
    public function setRemoteIp(int $value)
    {
        $this->setData(self::REMOTE_IP, $value);
        return $this;
    }

    /**
     * @return array|mixed|string|null
     */
    public function getStoreName()
    {
        return $this->getData(self::STORE_NAME);
    }

    /**
     * @param int $value
     * @return $this|ParentOrderDetailInterface
     */
    public function setStoreName(int $value)
    {
        $this->setData(self::STORE_NAME, $value);
        return $this;
    }

    /**
     * @return array|mixed|string|null
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @param string $value
     * @return $this|ParentOrderDetailInterface
     */
    public function setCreatedAt(string $value)
    {
        $this->setData(self::CREATED_AT, $value);
        return $this;
    }

    /**
     * @param $value
     * @return ParentOrderDetail
     */
    public function setShippingAddress($value)
    {
        return $this->setData('shipping_address_id', $value);
    }

    /**
     * @param $value
     * @return ParentOrderDetail
     */
    public function setBillingAddress($value)
    {
        return $this->setData('billing_address_id', $value);
    }

    /**
     * Retrieve store model instance
     *
     * @return \Magento\Store\Api\Data\StoreInterface
     */
    public function getStore()
    {
        $storeId = $this->getStoreId();
        if ($storeId) {
            return $this->_storeManager->getStore($storeId);
        }
        return $this->_storeManager->getStore();
    }

    /**
     * @return string
     */
    public function getEntityType()
    {
        return $this->entityType;
    }

    /**
     * @return array|int|mixed|null
     */
    public function getCustomerGroupId()
    {
        return $this->getData(self::GROUP_ID);
    }

    /**
     * @param int $value
     * @return ParentOrderDetail
     */
    public function setCustomerGroupId(int $value)
    {
        return $this->setData(self::GROUP_ID, $value);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getShippingDescription()
    {
        return $this->getData(self::SHIPPING_DESCRIPTION);
    }

    /**
     * @param int $value
     * @return ParentOrderDetail
     */
    public function setShippingDescription(int $value)
    {
        return $this->setData(self::SHIPPING_DESCRIPTION, $value);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getPaymentMethod()
    {
        return $this->getData(self::PAYMENT_METHOD);
    }

    /**
     * @param string $value
     * @return ParentOrderDetail
     */
    public function setPaymentMethod(string $value)
    {
        return $this->setData(self::PAYMENT_METHOD, $value);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getOrderCurrencyCode()
    {
        return $this->getData(self::ORDER_CURRENCY_CODE);
    }

    /**
     * @param string $value
     * @return ParentOrderDetail
     */
    public function setOrderCurrencyCode(string $value)
    {
        return $this->setData(self::ORDER_CURRENCY_CODE, $value);
    }

    /**
     * @return string|null
     */
    public function getFrontendStatusLabel()
    {
        return $this->statusLabel->getStatusFrontendLabel(
            $this->getStatus(),
            Area::AREA_FRONTEND,
            (int)$this->getStoreId()
        );
    }

    /**'
     * @return array|mixed|string|null
     */
    public function getCustomerIsGuest()
    {

        return $this->getData(self::CUSTOMER_IS_GUEST);
    }

    /**
     * @param int $value
     * @return ParentOrderDetail
     */
    public function setCustomerIsGuest(int $value)
    {
        return $this->setData(self::CUSTOMER_IS_GUEST, $value);
    }

    /**
     * Get order is not virtual
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsNotVirtual()
    {
        return !$this->getIsVirtual();
    }

    /**
     * @return array|mixed|null
     */
    public function getCustomerNote()
    {
        return (string)$this->getData(self::CUSTOMER_NOTE);
    }

    /**
     * @return string
     */
    public function getShippingMethod()
    {
        return (string)$this->getData(self::SHIPPING_METHOD);
    }

    /**
     * @param string $value
     * @return ParentOrderDetail
     */
    public function setShippingMethod(string $value)
    {
        return $this->setData(self::SHIPPING_METHOD, $value);
    }

    /**
     * @return array|int|mixed|null
     */
    public function getParentRelationNewId()
    {
        return $this->getData(self::PARENT_RELATION_NEW_ID);
    }

    /**
     * @param int $value
     * @return ParentOrderDetail
     */
    public function setParentRelationNewId(int $value)
    {
        return $this->setData(self::PARENT_RELATION_NEW_ID, $value);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getParentRelationNewRealId()
    {
        return $this->getData(self::PARENT_RELATION_NEW_REAL_ID);
    }

    /**
     * @param string $value
     * @return ParentOrderDetail
     */
    public function setParentRelationNewRealId(string $value)
    {
        return $this->setData(self::PARENT_RELATION_NEW_REAL_ID, $value);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getOrderNote()
    {
        return (string)$this->getData(self::ORDER_NOTE);
    }

    public function setOrderNote(string $value)
    {
        return $this->setData(self::ORDER_NOTE, $value);
    }

    /**
     * @return string
     */
    public function getReferrerCode()
    {
        return (string)$this->getData(self::REFERER_CODE);
    }

    /**
     * @param string $value
     * @return ParentOrderDetail
     */
    public function setReferrerCode(string $value)
    {
        return $this->setData(self::REFERER_CODE, $value);
    }

    /**
     * @return string
     */
    public function getEcpayInvoiceCarruerNum()
    {
        return (string)$this->getData(self::ECPAY_INVOICE_CARRUER_NUM);
    }

    /**
     * @param string $value
     * @return ParentOrderDetail
     */
    public function setEcpayInvoiceCarruerNum(string $value)
    {
        return $this->setData(self::ECPAY_INVOICE_CARRUER_NUM, $value);
    }


    /**
     * @return string
     */
    public function getEcpayInvoiceCarruerType()
    {
        return (string)$this->getData(self::ECPAY_INVOICE_CARRUER_TYPE);
    }

    /**
     * @param string $value
     * @return ParentOrderDetail
     */
    public function setEcpayInvoiceCarruerType(string $value)
    {
        return $this->setData(self::ECPAY_INVOICE_CARRUER_TYPE, $value);
    }


    /**
     * @return string
     */
    public function getEcpayInvoiceCustomerCompany()
    {
        return (string)$this->getData(self::ECPAY_INVOICE_CUSTOMER_COMPANY);
    }

    /**
     * @param string $value
     * @return ParentOrderDetail
     */
    public function setEcpayInvoiceCustomerCompany(string $value)
    {
        return $this->setData(self::ECPAY_INVOICE_CUSTOMER_COMPANY, $value);
    }


    /**
     * @return string
     */
    public function getEcpayInvoiceCustomerIdentifier()
    {
        return (string)$this->getData(self::ECPAY_INVOICE_CUSTOMER_IDENTIFIER);
    }

    /**
     * @param string $value
     * @return ParentOrderDetail
     */
    public function setEcpayInvoiceCustomerIdentifier(string $value)
    {
        return $this->setData(self::ECPAY_INVOICE_CUSTOMER_IDENTIFIER, $value);
    }

    /**
     * @return string
     */
    public function getRmaStatus()
    {
        return (string)$this->getData(self::RMA_STATUS);
    }

    /**
     * @param string $value
     * @return ParentOrderDetail
     */
    public function setRmaStatus(string $value)
    {
        return $this->setData(self::RMA_STATUS, $value);
    }

}
