<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderFrontendUi\Controller\ParentOrder;

use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Sales\Controller\OrderInterface;
use Branch8\MarketPlaceParentOrderFrontendUi\Controller\ParentOrder\AbstractController\Creditmemo as AbstractCreditNemo;

class Creditmemo extends AbstractCreditNemo implements OrderInterface, HttpGetActionInterface
{
}
