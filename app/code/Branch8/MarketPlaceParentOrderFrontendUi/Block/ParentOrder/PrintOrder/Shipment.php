<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderFrontendUi\Block\ParentOrder\PrintOrder;

use Branch8\MarketPlaceParentOrder\Api\ParentOrderManagementInterface;
use Branch8\MarketPlaceParentOrder\Model\ParentOrder;
use Magento\Framework\View\Element\AbstractBlock;

class Shipment extends \Magento\Sales\Block\Items\AbstractItems
{
    /**
     * Tracks for Shippings
     *
     * @var array
     */
    protected $tracks = [];

    /**
     * Order shipments collection
     *
     * @var array|\Magento\Sales\Model\ResourceModel\Order\Shipment\Collection
     */
    protected $shipmentsCollection;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

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
        $this->coreRegistry = $registry;
        $this->addressRenderer = $addressRenderer;
        parent::__construct($context, $data);
    }

    /**
     * Load all tracks and save it to local cache by shipments
     *
     * @return $this
     */
    protected function _beforeToHtml()
    {
        $tracksCollection = $this->getOrder()->getTracksCollection();
        foreach ($tracksCollection as $track) {
            $shipmentId = $track->getParentId();
            $this->tracks[$shipmentId][] = $track;
        }
        $shipment = $this->coreRegistry->registry('current_shipment');
        if ($shipment) {
            $this->shipmentsCollection = [$shipment];
        } else {
            $this->shipmentsCollection = $this->getOrder()->getShipmentsCollection();
        }

        return parent::_beforeToHtml();
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
        return $this->getChildHtml('payment_info');
    }

    /**
     * @return ParentOrder
     */
    public function getOrder()
    {
        return $this->coreRegistry->registry('current_parent_order');
    }

    /**
     * @return array|null
     */
    public function getShipment()
    {
        return $this->coreRegistry->registry('current_shipment');
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
     * Retrieve order shipments collection
     *
     * @return array|\Magento\Sales\Model\ResourceModel\Order\Shipment\Collection
     */
    public function getShipmentsCollection()
    {
        return $this->getOrder()->getShipmentCollection();
    }

    /**
     * Getter for order tracking numbers collection per shipment
     *
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return array
     */
    public function getShipmentTracks($shipment)
    {
        $tracks = [];
        if (!empty($this->tracks[$shipment->getId()])) {
            $tracks = $this->tracks[$shipment->getId()];
        }
        return $tracks;
    }

    /**
     * Getter for shipment address by format
     *
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return string
     */
    public function getShipmentAddressFormattedHtml($shipment)
    {
        return $this->addressRenderer->getFormattedAddress($this->getOrder()->getShippingAddress());
    }

    /**
     * Getter for billing address of order by format
     *
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    public function getBillingAddressFormattedHtml($order)
    {
        return $this->addressRenderer->getFormattedAddress(
            $this->getOrder()->getBillingAddress()
        );
    }

    /**
     * Getter for billing address of order by format
     *
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return array
     */
    public function getShipmentItems($shipment)
    {
        $res = [];
        foreach ($shipment->getItemsCollection() as $item) {
            if (!$item->getOrderItem()->getParentItem()) {
                $res[] = $item;
            }
        }
        return $res;
    }
}
