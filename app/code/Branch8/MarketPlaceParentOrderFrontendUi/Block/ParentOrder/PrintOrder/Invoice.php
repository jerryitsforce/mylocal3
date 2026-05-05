<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderFrontendUi\Block\ParentOrder\PrintOrder;

use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderAddress;
use Magento\Framework\View\Element\AbstractBlock;

/**
 * Sales order details block
 *
 * @api
 * @since 100.0.2
 */
class Invoice extends \Magento\Sales\Block\Items\AbstractItems
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var ParentOrderManagementInterface
     */
    protected $parentOrderManagement;

    /**
     * @var \Branch8\MarketPlaceParentOrder\Model\Services\FormatAddress
     */
    protected $addressRenderer;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param ParentOrderManagementInterface $parentOrderManagement
     * @param \Branch8\MarketPlaceParentOrder\Model\Services\FormatAddress $addressRenderer
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context             $context,
        \Magento\Framework\Registry                                  $registry,
        ParentOrderManagementInterface                               $parentOrderManagement,
        \Branch8\MarketPlaceParentOrder\Model\Services\FormatAddress $addressRenderer,
        array                                                        $data = []
    )
    {
        $this->parentOrderManagement = $parentOrderManagement;
        $this->_coreRegistry = $registry;
        $this->addressRenderer = $addressRenderer;
        parent::__construct($context, $data);
    }

    /**
     * @return void
     */
    protected function _prepareLayout()
    {
        $this->pageConfig->getTitle()->set(__('Order # %1',
                $this->getOrder()->getDetail()->getIncrementId())
        );
    }

    /**
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('*/*/history');
    }

    /**
     * @return string
     */
    public function getPrintUrl()
    {
        return $this->getUrl('*/*/print');
    }

    /**
     * @return string
     */
    public function getPaymentInfoHtml()
    {
        return $this->parentOrderManagement->getPaymentHtml($this->getOrder());
    }

    /**
     * @return ParentOrder
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_parent_order');
    }

    /**
     * @return array|null
     */
    public function getInvoice()
    {
        return $this->_coreRegistry->registry('current_invoice');
    }

    /**
     * @param AbstractBlock $renderer
     * @return $this
     */
    protected function _prepareItem(AbstractBlock $renderer)
    {
        $renderer->setPrintStatus(true);
        return parent::_prepareItem($renderer);
    }

    /**
     * Get html of invoice totals block
     *
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return  string
     */
    public function getInvoiceTotalsHtml($invoice)
    {
        $html = '';
        $totals = $this->getChildBlock('invoice_totals');
        if ($totals) {
            $totals->setInvoice($invoice);
            $html = $totals->toHtml();
        }
        return $html;
    }

    /**
     * @param ParentOrderAddress $address
     * @return null
     */
    public function getFormattedAddress(ParentOrderAddress $address)
    {
        return $this->addressRenderer->getFormattedAddress($address);
    }

    /**
     * @param ParentOrderAddress $address
     * @return null
     */
    public function formatAddress(ParentOrderAddress $address)
    {
        return $this->getFormattedAddress($address);
    }

    /**
     * @param $invoice
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getInvoiceTotalHtml(\Magento\Sales\Model\Order\Invoice $invoice)
    {
        if (!$invoice->getOrder()) {
            return '';
        };
        $html = $this->getLayout()->createBlock(
            \Magento\Sales\Block\Order\Invoice\Totals::class
        )->setOrder($invoice->getOrder())
            ->setTemplate('Magento_Sales::order/totals.phtml')
            ->setInvoice($invoice)->toHtml();
        return $html;
    }
}
