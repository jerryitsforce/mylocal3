<?php
declare(strict_types=1);

namespace Branch8\MarketPlaceParentOrderFrontendUi\Controller\ParentOrder;
use Branch8\MarketPlaceParentOrderFrontendUi\Controller\ParentOrder\AbstractController\View as AbtractView;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Sales\Controller\OrderInterface;

class View extends AbtractView implements OrderInterface, HttpGetActionInterface
{
}
