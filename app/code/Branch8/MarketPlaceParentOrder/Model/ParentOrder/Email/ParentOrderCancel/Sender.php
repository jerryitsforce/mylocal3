<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrder\Model\ParentOrder\Email\ParentOrderCancel;

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
use Magento\Framework\UrlInterface;
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
    protected $registry;

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
        \Magento\Framework\Registry $registry,
        \Branch8\FlagshipStore\Helper\Sales $flagshipStoreSaleHelper
    )
    {
        $this->registry = $registry;
        $this->parentOrderManagementFactory = $parentOrderManagementFactory;
        $this->_localeDate = $localeDate;
        $this->_url = $urlInterface;
        $this->resource = $resource;
        $this->flagshipStoreSaleHelper = $flagshipStoreSaleHelper;
        parent::__construct($templateContainer, $identityContainer, $senderBuilderFactory, $scopeConfig, $log, $formatAddress, $addressRenderer, $emulation, $eventManager, $customerNickname);
    }

    const XML_PATH_SHOW_STATUS = 'parent_order/parent_order_configuration/show_status';
    const CRON_CANCEL = 'parent_order_auto_cancel';

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
        $subTotal = $totalInformation->getTotalByKey($parentOrder, 'subtotal')->getBaseSubtotalInclTax();
//        $subTotal -= $flagshipProcessOrderValue;

        $pointDiscount = $totalInformation->getTotalByKey($parentOrder, 'point_used_total')->getValue();
        $shippingAmount = $totalInformation->getTotalByKey($parentOrder, 'shipping_include_tax')->getValue();
//        $shippingAmount += $flagshipProcessOrderValue;

        $paid = $totalInformation->getTotalByKey($parentOrder, 'paid')->getValue();
        $isAutoCancelled = $this->registry->registry(self::CRON_CANCEL);
        $transport = [
            'order' => $parentOrder,
            'increment_id' => $parentOrder->getDetail()->getIncrementId(),
            'detail' => $parentOrder->getDetail(),
            'show_status' => (bool)$this->scopeConfig->getValue(self::XML_PATH_SHOW_STATUS),
            'parent_order_id' => $parentOrder->getId(),
            'order_data' => [
                'customer_name' => $customerName,
                'frontend_status_label' => $parentOrder->getDetail()->getFrontendStatusLabel(),
                'is_not_virtual' => $parentOrder->getDetail()->getIsNotVirtual(),
                'email_customer_note' => $parentOrder->getDetail()->getCustomerNote(),
                'paid' => $isAutoCancelled ?
                    $parentOrder->getOrderCurrency()->formatPrecision(0, 2) :
                    $parentOrder->getOrderCurrency()->formatPrecision($paid, 2),
                'subtotal' => $parentOrder->getOrderCurrency()->formatPrecision($subTotal, 2),
                'point_discount' => $parentOrder->getOrderCurrency()->formatPrecision($pointDiscount, 2),
                'shipping_amount' => $parentOrder->getOrderCurrency()->formatPrecision($shippingAmount, 2),
                'is_point' => $pointDiscount != 0,
                'created_at' => $this->getOrderDate($parentOrder->getDetail()->getCreatedAt()),
                'url_history' => $this->getOrderUrlDetail($parentOrder->getId()),
                'is_auto_cancel' => $isAutoCancelled
            ]
        ];
        $transportObject = new DataObject($transport);
        $this->appEmulation->stopEnvironmentEmulation();

        /**
         * Event argument `transport` is @deprecated. Use `transportObject` instead.
         */
        $this->eventManager->dispatch(
            'email_parent_order_cancel_set_template_vars_before',
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
     * @param $id
     * @return string|null
     */
    public function getOrderUrlDetail($id){
        $hotaiParentOrderNumber = $this->getParentOrderInfo($id);
        if($hotaiParentOrderNumber){
            return $this->_url->getUrl('sales/parentOrder/history', ['_query' => ['search' => $hotaiParentOrderNumber]]);
        }
        return $this->_url->getUrl('sales/parentOrder/history');
    }


    /**
     * @param $childId
     * @return false|mixed
     */
    public function getParentOrderInfo($id){
        try {
            // Initialize the database connection
            $connection = $this->getConnection();
            // Build the select query
            $select = $connection->select()
                ->from(['spod' => $this->resource->getTableName('sales_parent_order_detail')], ['hotai_parent_order_number'])
                ->where('spod.entity_id = ?', $id);

            // Fetch the results
            $result = $connection->fetchRow($select);

            // Check the condition when hotai_parent_order_number is empty
            if (empty($result)) {
                return false;
            } else {
                return $result['hotai_parent_order_number'];
            }

        } catch (\Exception $e) {
            return false;
        }

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
