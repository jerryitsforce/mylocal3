<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderFrontendUi\Block\ParentOrder;

use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Magento\Customer\Model\Context;

class Creditmemo extends \Magento\Sales\Block\Order\Creditmemo\Items
{
    /**
     * @var string
     */
    protected $_template = 'Branch8_MarketPlaceParentOrderFrontendUi::parent_order/creditmemo.phtml';

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $parentOrderManagement;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param ParentOrderManagementInterface $parentOrderManagement
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry                      $registry,
        \Magento\Framework\App\Http\Context              $httpContext,
        ParentOrderManagementInterface                   $parentOrderManagement,
        array                                            $data = []
    )
    {
        $this->parentOrderManagement = $parentOrderManagement;
        $this->httpContext = $httpContext;
        parent::__construct($context, $registry, $data);
    }

    /**
     * @return void
     */
    protected function _prepareLayout()
    {
        $this->pageConfig->getTitle()->set(
            __('Order # %1', $this->getOrder()->getDetail()->getIncrementId())
        );
    }

    /**
     * @return string
     */
    public function getPaymentInfoHtml()
    {
        return $this->parentOrderManagement->getPaymentHtml($this->getOrder());
    }

    /**
     * Retrieve current order model instance
     *
     * @return ParentOrder
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_parent_order');
    }

    /**
     * Return back url for logged in and guest users
     *
     * @return string
     */
    public function getBackUrl()
    {
        if ($this->httpContext->getValue(Context::CONTEXT_AUTH)) {
            return $this->getUrl('*/*/history');
        }
        return $this->getUrl('*/*/form');
    }

    /**
     * Return back title for logged in and guest users
     *
     * @return \Magento\Framework\Phrase
     */
    public function getBackTitle()
    {
        if ($this->httpContext->getValue(Context::CONTEXT_AUTH)) {
            return __('Back to My Orders');
        }
        return __('View Another Order');
    }

    /**
     * @param object $order
     * @return string
     */
    public function getInvoiceUrl($order)
    {
        return $this->getUrl('*/*/invoice', ['id' => $order->getId()]);
    }

    /**
     * @param object $order
     * @return string
     */
    public function getShipmentUrl($order)
    {
        return $this->getUrl('*/*/shipment', ['id' => $order->getId()]);
    }

    /**
     * @param object $order
     * @return string
     */
    public function getViewUrl($order)
    {
        return $this->getUrl('*/*/view', ['id' => $order->getId()]);
    }

    /**
     * @param object $creditmemo
     * @return string
     */
    public function getPrintCreditmemoUrl($creditmemo)
    {
        return $this->getUrl('*/order/printCreditmemo', ['creditmemo_id' => $creditmemo->getId()]);
    }

    /**
     * @param object $order
     * @return string
     */
    public function getPrintAllCreditmemosUrl($order)
    {
        return $this->getUrl('*/*/printcreditmemo', ['id' => $order->getId()]);
    }
}
