<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Model\Restriction\RelatedOrder\Collection;

use Amasty\Rolepermissions\Helper\Data as Helper;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Magento\Rma\Model\ResourceModel\Rma\Collection as RmaCollection;
use Magento\Rma\Model\ResourceModel\Rma\Grid\Collection as RmaGridCollection;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection as CreditmemoCollection;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Grid\Collection as CreditmemoGridCollection;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Branch8\AdvancedPermissions\Block\Adminhtml\Role\Tab\Seller;
use Magento\Sales\Model\ResourceModel\Order\Invoice\Collection as InvoiceCollection;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Collection as ShipmentCollection;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Grid\Collection as ShipmentGridCollection;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction\Collection as PaymentTransactionCollection;
use Magento\Sales\Model\ResourceModel\Transaction\Grid\Collection as PaymentTransactionGridCollection;
use Webkul\MarketplacePreorder\Model\ResourceModel\PreorderItems\Collection as PreorderItemsCollection;
use Webkul\MarketplacePreorder\Model\ResourceModel\PreorderItems\Grid\Collection as PreorderItemsGridCollection;

class StandardRestrict implements RestrictInterface
{
    /**
     * @var array
     */
    private $restrictedObjects = [];

    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var State
     */
    private $appState;

    public function __construct(
        Helper $helper,
        State $appState
    ) {
        $this->helper = $helper;
        $this->appState = $appState;
    }

    public function execute(InvoiceCollection|ShipmentCollection|ShipmentGridCollection|CreditmemoCollection|CreditmemoGridCollection|RmaCollection|RmaGridCollection|SearchResult|PaymentTransactionCollection|PaymentTransactionGridCollection|PreorderItemsCollection|PreorderItemsGridCollection $collection): void
    {
        try {
            if (!in_array($this->appState->getAreaCode(), Helper::ALLOWED_AREA_CODES)) {
                return;
            }
        } catch (LocalizedException $e) {
            return;
        }

        $objectId = spl_object_hash($collection);

        if (isset($this->restrictedObjects[$objectId])) {
            return;
        }

        $rule = $this->helper->currentRule();
        if (is_object($rule)) {
            $this->restrictOrderCollection($rule, $collection);
            $this->restrictedObjects[$objectId] = true;
        }
    }

    public function restrictOrderCollection($rule, InvoiceCollection|ShipmentCollection|ShipmentGridCollection|CreditmemoCollection|CreditmemoGridCollection|RmaCollection|RmaGridCollection|SearchResult|PaymentTransactionCollection|PaymentTransactionGridCollection|PreorderItemsCollection|PreorderItemsGridCollection $collection): void
    {
        switch ($rule->getSellerAccessMode()) {
            case Seller::MODE_ANY:
                break;
            case Seller::MODE_SELECTED:
                if ($rule->getSellers()) {
                    $subQuery = new \Zend_Db_Expr('(SELECT DISTINCT order_id as sub_order_id, seller_id from marketplace_orders where seller_id in ('.implode(',',$rule->getSellers()).'))');
                    $collection->getSelect()->join(
                        ['mo' => $subQuery],
                        'main_table.order_id = mo.sub_order_id',
                        []
                    );
                }
                break;
        }
    }
}
