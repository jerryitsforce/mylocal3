<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderFrontendUi\Controller\ParentOrder;

use Magento\Sales\Controller\OrderInterface;
use Branch8\MarketPlaceParentOrderFrontendUi\Controller\ParentOrder\AbstractController\Reorder as AbstractReoder;

class Reorder extends AbstractReoder implements OrderInterface
{
}
