<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Branch8\MarketplaceStaging\Block\Order\Creditmemo;

/**
 * Webkul Marketplace Order Creditmemo History Block.
 */
use Magento\Sales\Model\Order;
use Magento\Customer\Model\Customer;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order\Creditmemo;

class History extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Customer\Model\Customer
     */
    protected $Customer;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $Order;

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var \Webkul\Marketplace\Helper\Orders
     */

    protected $ordersHelper;

    /**
     * @var Creditmemo
     */
    protected $creditmemoModel;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param Order                                            $order
     * @param Customer                                         $customer
     * @param \Magento\Framework\Registry                      $coreRegistry
     * @param \Magento\Customer\Model\Session                  $customerSession
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Webkul\Marketplace\Helper\Orders                $ordersHelper
     * @param Creditmemo                                       $creditmemoModel
     * @param array                                            $data
     */
    public function __construct(
        Order $order,
        Customer $customer,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\View\Element\Template\Context $context,
        \Webkul\Marketplace\Helper\Orders $ordersHelper = null,
        Creditmemo $creditmemoModel = null,
        array $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->Customer = $customer;
        $this->Order = $order;
        $this->_customerSession = $customerSession;
        $this->ordersHelper = $ordersHelper ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(\Webkul\Marketplace\Helper\Orders::class);
        $this->creditmemoModel = $creditmemoModel ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->create(Creditmemo::class);
        parent::__construct($context, $data);
    }

    /**
     * Retrieve current order model instance.
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('sales_order');
    }

    /**
     * Set title
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->pageConfig->getTitle()->set(__('My Orders'));
    }

    /**
     * Get customer id
     *
     * @return int
     */
    public function getCustomerId()
    {
        return $this->_customerSession->getCustomerId();
    }

    /**
     * Get collection
     *
     * @return bool|\Magento\Sales\Model\Order\Creditmemo\Collection
     */
    public function getCollection()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $creditmemo = $this->creditmemoModel
            ->getCollection()
            ->addFieldToFilter(
                'order_id', $orderId
            );
        return $creditmemo;
    }

     /**
      * Set collection
      *
      * @return $this
      */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if ($this->getCollection()) {
            $pager = $this->getLayout()->createBlock(
                \Magento\Theme\Block\Html\Pager::class,
                'marketplace.order.creditmemo.pager'
            )->setCollection(
                $this->getCollection()
            );
            $this->setChild('pager', $pager);
            $this->getCollection()->load();
        }
        return $this;
    }

    /**
     * Get Pager
     *
     * @return string
     */
    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }

    /**
     * Get current url
     *
     * @return string
     */
    public function getCurrentUrl()
    {
        return $this->_urlBuilder->getCurrentUrl(); // Give the current url of recently viewed page
    }
}
