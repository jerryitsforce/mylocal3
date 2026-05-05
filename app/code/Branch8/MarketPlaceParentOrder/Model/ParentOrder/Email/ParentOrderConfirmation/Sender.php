<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\ParentOrder\Email\ParentOrderConfirmation;

use Branch8\Customer\Model\GetCustomerNickname;
use Branch8\MarketPlaceParentOrder\Helper\Log;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderDetail;
use Branch8\MarketPlaceParentOrder\Model\Services\FormatAddress;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\Event\Manager;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Sales\Model\Order\Email\Container\IdentityInterface;
use Magento\Sales\Model\Order\Email\Container\Template;
use Magento\Store\Model\App\Emulation;

class Sender extends ParentOrder\Email\AbstractNotifySender implements SenderInterface
{
    /**
     * @var \Branch8\MarketPlaceParentOrder\Model\ParenOrderManagementFactory
     */
    protected $parentOrderManagementFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;
    /**
     * @var \Magento\Framework\Url
     */
    protected $_url;
    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var string
     */
    protected $connectionName;

    /**
     * @var AdapterInterface
     */
    protected $connection;
    /**
     * @var \Branch8\FlagshipStore\Helper\Sales
     */
    protected $flagshipStoreSaleHelper;

    /**
     * @param Template $templateContainer
     * @param IdentityInterface $identityContainer
     * @param ParentOrder\Email\SenderBuilderFactory $senderBuilderFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param Log $log
     * @param FormatAddress $formatAddress
     * @param Renderer $addressRenderer
     * @param Emulation $emulation
     * @param Manager $eventManager
     * @param \Branch8\MarketPlaceParentOrder\Model\ParenOrderManagementFactory $parentOrderManagementFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Framework\Url $urlInterface
     * @param ResourceConnection $resource
     * @param GetCustomerNickname $customerNickname
     * @param \Branch8\FlagshipStore\Helper\Sales $flagshipStoreSaleHelper
     */
    public function __construct(
        Template $templateContainer,
        IdentityInterface $identityContainer,
        \Branch8\MarketPlaceParentOrder\Model\ParentOrder\Email\SenderBuilderFactory $senderBuilderFactory,
        ScopeConfigInterface $scopeConfig,
        Log $log,
        FormatAddress $formatAddress, Renderer $addressRenderer,
        Emulation $emulation,
        Manager $eventManager,
        \Branch8\MarketPlaceParentOrder\Model\ParenOrderManagementFactory $parentOrderManagementFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Url $urlInterface,
        ResourceConnection $resource,
        GetCustomerNickname $customerNickname,
        \Branch8\FlagshipStore\Helper\Sales $flagshipStoreSaleHelper

    )
    {
        $this->parentOrderManagementFactory = $parentOrderManagementFactory;
        $this->_localeDate = $localeDate;
        $this->_url = $urlInterface;
        $this->resource = $resource;
        $this->flagshipStoreSaleHelper = $flagshipStoreSaleHelper;
        parent::__construct($templateContainer, $identityContainer, $senderBuilderFactory, $scopeConfig, $log, $formatAddress, $addressRenderer, $emulation, $eventManager, $customerNickname);
    }

    const XML_PATH_SHOW_STATUS = 'parent_order/parent_order_configuration/show_status';

    /**
     * @param ParentOrder $parentOrder
     * @return bool|mixed
     */
    public function send(ParentOrder $parentOrder)
    {
        $customerName = $this->customerNickname->getCustomerNicknameByCustomerId($parentOrder->getDetail()->getCustomerId());
        $this->identityContainer->setStore($parentOrder->getStore());
        $this->identityContainer->setCustomerName($customerName);
        $this->identityContainer->setCustomerEmail($parentOrder->getDetail()->getCustomerEmail());
        $this->appEmulation->startEnvironmentEmulation(
            $parentOrder->getDetail()->getStoreId(),
            Area::AREA_FRONTEND, true
        );
//        $flagshipProcessOrderValue = $this->flagshipStoreSaleHelper->getProcessOrderValue($parentOrder->getDetail()->getParentId());
        /**
         * @var $detail ParentOrderDetail
         */
        $totalInformation = $this->parentOrderManagementFactory->create();
        $grandTotal = $totalInformation->getTotalByKey($parentOrder, 'grand_total')->getValue();
        $subTotal = $totalInformation->getTotalByKey($parentOrder, 'subtotal')->getBaseSubtotalInclTax();
//        $subTotal -= $flagshipProcessOrderValue;

        $pointDiscount = $totalInformation->getTotalByKey($parentOrder, 'point_discount')->getValue();
        $shippingAmount = $totalInformation->getTotalByKey($parentOrder, 'shipping_include_tax')->getValue();
//        $shippingAmount += $flagshipProcessOrderValue;

        $parentOrder->getDetail()->getCustomer();
        $transport = [
            'order' => $parentOrder,
            'increment_id' => $parentOrder->getDetail()->getIncrementId(),
            'detail' => $parentOrder->getDetail(),
            'show_status' => (bool)$this->scopeConfig->getValue(self::XML_PATH_SHOW_STATUS),
            'parent_order_id' => $parentOrder->getId(),
            'store' =>  $parentOrder->getStore(),
            'order_data' => [
                'customer_name' => $customerName,
                'frontend_status_label' => $parentOrder->getDetail()->getFrontendStatusLabel(),
                'is_not_virtual' => $parentOrder->getDetail()->getIsNotVirtual(),
                'email_customer_note' => $parentOrder->getDetail()->getCustomerNote(),
                'grandTotal' => $parentOrder->getOrderCurrency()->formatPrecision($grandTotal, 2),
                'subtotal' => $parentOrder->getOrderCurrency()->formatPrecision($subTotal, 2),
                'point_discount' => $parentOrder->getOrderCurrency()->formatPrecision($pointDiscount, 2),
                'shipping_amount' => $parentOrder->getOrderCurrency()->formatPrecision($shippingAmount, 2),
                'is_point' => $pointDiscount != 0,
                'created_at' => $this->getOrderDate($parentOrder->getDetail()->getCreatedAt()),
                'url_history' => $this->getOrderUrlDetail($parentOrder->getDetail()->getIncrementId()),
            ]
        ];
        $transportObject = new DataObject($transport);
        $this->appEmulation->stopEnvironmentEmulation();

        /**
         * Event argument `transport` is @deprecated. Use `transportObject` instead.
         */
        $this->eventManager->dispatch(
            'email_parent_order_set_template_vars_before',
            ['sender' => $this, 'transport' => $transportObject->getData(), 'transportObject' => $transportObject]
        );

        $this->templateContainer->setTemplateVars($transportObject->getData());
        return $this->checkAndSend($parentOrder);
    }

    /**
     * @param $createdAt
     * @return string
     */
    public function getOrderDate($createdAt)
    {
        try {
            return $this->_localeDate->date($createdAt)->format('Y/m/d H:i');
        }catch (\Exception $exception){
            return '';
        }
    }

    /**
     * @return ParentOrderManagementInterface|mixed
     */
    private function getOrderMangement()
    {
        return ObjectManager::getInstance()->get(ParentOrderManagementInterface::class);
    }

    /**
     * @return string|null
     */
    public function getOrderUrlDetail($parentIncrementId){
        if($parentIncrementId){
            return $this->_url->getUrl('sales/parentOrder/history', ['_query' => ['search' => $parentIncrementId]]);
        }
        return $this->_url->getUrl('sales/parentOrder/history');
    }

    /**
     * @return AdapterInterface|\Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected function getConnection()
    {
        if (!$this->connection) {
            $this->connection = $this->resource->getConnection($this->connectionName);
        }
        return $this->connection;
    }
}
