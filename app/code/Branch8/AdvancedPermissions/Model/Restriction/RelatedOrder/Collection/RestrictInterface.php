<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Model\Restriction\RelatedOrder\Collection;

use Magento\Sales\Model\ResourceModel\Order\Invoice\Collection as InvoiceCollection;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Collection as ShipmentCollection;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Grid\Collection as ShipmentGridCollection;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection as CreditmemoCollection;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Grid\Collection as CreditmemoGridCollection;
use Magento\Rma\Model\ResourceModel\Rma\Collection as RmaCollection;
use Magento\Rma\Model\ResourceModel\Rma\Grid\Collection as RmaGridCollection;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\Collection as PaymentTransactionCollection;
use Magento\Sales\Model\ResourceModel\Transaction\Grid\Collection as PaymentTransactionGridCollection;
use Webkul\MarketplacePreorder\Model\ResourceModel\PreorderItems\Collection as PreorderItemsCollection;
use Webkul\MarketplacePreorder\Model\ResourceModel\PreorderItems\Grid\Collection as PreorderItemsGridCollection;

interface RestrictInterface
{
    public function execute(InvoiceCollection|ShipmentCollection|ShipmentGridCollection|CreditmemoCollection|CreditmemoGridCollection|RmaCollection|RmaGridCollection|SearchResult|PaymentTransactionCollection|PaymentTransactionGridCollection|PreorderItemsCollection|PreorderItemsGridCollection $collection): void;
}
