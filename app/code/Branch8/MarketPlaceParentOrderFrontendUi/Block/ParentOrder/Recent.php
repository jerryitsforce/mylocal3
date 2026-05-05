<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderFrontendUi\Block\ParentOrder;

use Branch8\MarketPlaceParentOrder\Api\Data\ParentOrderInterface;
use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Model\Session;
use Magento\Sales\Model\Order\Config;
use Branch8\MarketPlaceParentOrder\Model\ResourceModel\ParentOrder\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ObjectManager;

/**
 * Sales order history block
 *
 * @api
 * @since 100.0.2
 */
class Recent extends \Magento\Framework\View\Element\Template
{
    /**
     * Limit of orders
     */
    const ORDER_LIMIT = 5;

    private $orders;
    /**
     * @var CollectionFactoryInterface
     */
    protected $parentOrderCollectionFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Sales\Model\Order\Config
     */
    protected $_orderConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    private $httpContext;

    private $config;

    /**
     * @param Context $context
     * @param CollectionFactory $parentOrderCollectionFactory
     * @param Session $customerSession
     * @param Config $orderConfig
     * @param ParentOrderManagementInterface $parentOrderManagement
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Branch8\MarketPlaceParentOrderFrontendUi\Model\Config $config
     * @param StoreManagerInterface|null $storeManager
     * @param array $data
     */
    public function __construct(
        Context                                                $context,
        CollectionFactory                                      $parentOrderCollectionFactory,
        Session                                                $customerSession,
        Config                                                 $orderConfig,
        ParentOrderManagementInterface                         $parentOrderManagement,
        \Magento\Framework\App\Http\Context                    $httpContext,
        \Branch8\MarketPlaceParentOrderFrontendUi\Model\Config $config,
        StoreManagerInterface                                  $storeManager = null,
        array                                                  $data = [],
    )
    {
        $this->config = $config;
        $this->httpContext = $httpContext;
        $this->parentOrderCollectionFactory = $parentOrderCollectionFactory;
        $this->_customerSession = $customerSession;
        $this->_orderConfig = $orderConfig;
        $this->parentOrderManagement = $parentOrderManagement;
        $this->storeManager = $storeManager ?: ObjectManager::getInstance()
            ->get(StoreManagerInterface::class);
        parent::__construct($context, $data);
    }

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        parent::_construct();
        $this->getRecentOrders();
    }

    /**
     * @param ParentOrderInterface $parentOrder
     * @return true
     */
    public function canReorder(ParentOrderInterface $parentOrder)
    {
        if ($this->config->allowReorderOOSProduct()) {
            return true;
        }
        return $this->parentOrderManagement->canReorder($parentOrder);
    }

    /**
     * Get recently placed orders. By default they will be limited by 5.
     */
    public function getOrders()
    {
        if (!($this->_customerSession->getCustomerId())) {
            return false;
        }
        if (!$this->orders) {
            $this->orders = $this->parentOrderCollectionFactory->create()
                ->joinDetail([
                        'increment_id' => 'increment_id',
                        'status' => 'status',
                        'created_at' => 'created_at',
                        'customer_id' => 'customer_id'
                    ]
                )->addCustomerFilter(
                    $this->_customerSession->getCustomerId()
                )->addFieldToFilter(
                    'detail.status',
                    ['in' => $this->_orderConfig->getVisibleOnFrontStatuses()]
                )->setOrder(
                    'detail.created_at',
                    'desc'
                )->setPageSize(
                    self::ORDER_LIMIT
                );
        }
        return $this->orders;
    }

    /**
     * Get order view URL
     *
     * @param object $order
     * @return string
     */
    public function getViewUrl($order)
    {
        return $this->getUrl('sales/parentOrder/view', ['id' => $order->getId()]);
    }

    /**
     * @param ParentOrderInterface $parentOrder
     * @return string
     */
    public function getPrintUrl($order)
    {
        if (!$this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH)) {
            return $this->getUrl('sales/parentOrder/GuestPrint', ['id' => $order->getId()]);
        }
        return $this->getUrl('sales/parentOrder/print', ['id' => $order->getId()]);
    }

    /**
     * @param ParentOrderInterface $parentOrder
     * @return false|string
     */
    public function getReorderPostData(ParentOrderInterface $parentOrder)
    {
        return json_encode(
            [
                'action' => $this->getUrl('sales/parentOrder/reorder'),
                'data' => ['id' => $parentOrder->getIndexId()]
            ]
        );
    }

    /**
     * Get order track URL
     *
     * @param object $order
     * @return string
     * @deprecated 102.0.3 Action does not exist
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getTrackUrl($order)
    {
        //phpcs:ignore Magento2.Functions.DiscouragedFunction
        trigger_error('Method is deprecated', E_USER_DEPRECATED);
        return '';
    }

    /**
     * @inheritDoc
     */
    protected function _toHtml()
    {
        if ($this->getOrders()->getSize() > 0) {
            return parent::_toHtml();
        }
        return '';
    }

    /**
     * Get reorder URL
     *
     * @param object $order
     * @return string
     */
    public function getReorderUrl($order)
    {
        return $this->getUrl('sales/parentOrder/reorder', ['id' => $order->getId()]);
    }

    /**
     * @param ParentOrderInterface $parentOrder
     * @return float
     */
    public function getGrandTotal(ParentOrderInterface $parentOrder)
    {
        try {
            $totals = $this->parentOrderManagement->getTotals($parentOrder);
            foreach ($totals as $total) {
                if ($total->getCode() === 'grand_total') {
                    return (float)$total->getValue();
                }
            }
            return 0;
        } catch (\Exception $exception) {
            return 0;
        }
    }

    /**
     * @return bool
     */
    public function showStatus()
    {
        return $this->config->showStatus();
    }
}
