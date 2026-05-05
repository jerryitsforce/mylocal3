<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderFrontendUi\Controller\ParentOrder;
use Branch8\MarketPlaceParentOrderFrontendUi\Controller\ParentOrder\AbstractController\Shipment as AbstractShipment;
use Magento\Sales\Controller\OrderInterface;

class Shipment extends AbstractShipment implements OrderInterface
{
}
