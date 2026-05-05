<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderFrontendUi\Block\ParentOrder;

use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Branch8\MarketPlaceParentOrder\Model\ParentOrderAddress;
use Magento\Framework\View\Element\AbstractBlock;
class PrintShipment extends \Magento\Sales\Block\Items\AbstractItems
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
    ) {
        $this->parentOrderManagement = $parentOrderManagement;
        $this->_coreRegistry = $registry;
        $this->addressRenderer = $addressRenderer;
        parent::__construct($context, $data);
    }

    /**
     * Preparing global layout.
     *
     * @return void
     */
    protected function _prepareLayout()
    {
        $this->pageConfig->getTitle()->set(__('Print Order # %1',
            $this->getOrder()->getDetail()->getIncrementId())
        );
    }

    /**
     * Get payment info child block html.
     *
     * @return string
     */
    public function getPaymentInfoHtml()
    {
        return $this->parentOrderManagement->getPaymentHtml($this->getOrder());
    }

    /**
     * Retrieve current order from registry.
     *
     * @return ParentOrder
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_parent_order');
    }

    /**
     * Disable pager for printing page
     *
     * @return bool
     * @since 100.1.9
     */
    public function isPagerDisplayed()
    {
        return false;
    }

    /**
     * Get order items
     *
     * @return \Magento\Framework\DataObject[]
     * @since 100.1.9
     */
    public function getItems()
    {
        if (!$this->getOrder()) {
            return [];
        }
        return $this->getOrder()->getAllItems();
    }

    /**
     * Prepare item before output.
     *
     * @param AbstractBlock $renderer
     * @return $this
     */
    protected function _prepareItem(AbstractBlock $renderer)
    {
        $renderer->setPrintStatus(true);
        return parent::_prepareItem($renderer);
    }

    /**
     * @param ParentOrderAddress $address
     * @return null
     */
    public function getFormattedAddress(ParentOrderAddress $address)
    {
        return $this->addressRenderer->getFormattedAddress($address);
    }
}
