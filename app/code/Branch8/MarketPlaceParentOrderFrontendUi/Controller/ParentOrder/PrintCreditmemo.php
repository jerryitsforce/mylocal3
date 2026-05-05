<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderFrontendUi\Controller\ParentOrder;

use Magento\Sales\Controller\OrderInterface;
use Branch8\MarketPlaceParentOrderFrontendUi\Controller\ParentOrder\AbstractController\PrintCreditmemo as AbstractPrintCreditmemo;
class PrintCreditmemo extends AbstractPrintCreditmemo implements OrderInterface
{
}
