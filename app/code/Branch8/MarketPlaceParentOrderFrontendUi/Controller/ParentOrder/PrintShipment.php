<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderFrontendUi\Controller\ParentOrder;

use Magento\Sales\Controller\OrderInterface;
use Branch8\MarketPlaceParentOrderFrontendUi\Controller\ParentOrder\AbstractController\PrintShipment as AbstractPrintShipment;
class PrintShipment extends AbstractPrintShipment implements OrderInterface
{
}
