<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Model\Restriction\SellerCustomer\Collection;

use Amasty\Rolepermissions\Helper\Data as Helper;
use Magento\Customer\Model\ResourceModel\Customer\Collection as CustomerCollection;
use Magento\Customer\Model\ResourceModel\Grid\Collection as CustomerGridCollection;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Branch8\AdvancedPermissions\Block\Adminhtml\Role\Tab\Seller;

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

    public function execute(CustomerCollection|CustomerGridCollection $customerCollection): void
    {
        try {
            if (!in_array($this->appState->getAreaCode(), Helper::ALLOWED_AREA_CODES)) {
                return;
            }
        } catch (LocalizedException $e) {
            return;
        }

        $objectId = spl_object_hash($customerCollection);

        if (isset($this->restrictedObjects[$objectId])) {
            return;
        }

        $rule = $this->helper->currentRule();
        if (is_object($rule)) {
            $this->restrictCustomerCollection($rule, $customerCollection);
            $this->restrictedObjects[$objectId] = true;
        }
    }

    public function restrictCustomerCollection($rule, CustomerCollection|CustomerGridCollection $collection): void
    {
        switch ($rule->getSellerAccessMode()) {
            case Seller::MODE_ANY:
                break;
            case Seller::MODE_SELECTED:
              /*  if ($collection->getMainTable() == 'customer_grid_flat') {
                    $collection->getSelect()
                        ->where('main_table.entity_id IN (?)', $rule->getSellers());
                    return;
                }*/
                if ($rule->getSellers()) {
                    $query = $collection->getSelect()->__toString();
                    if (strpos($query, 'sales_order_grid') == false) {
                        $collection->getSelect()->joinLeft(
                            ['sog' => 'sales_order_grid'],
                            'main_table.entity_id = sog.customer_id',
                            ['order_ids' => 'group_concat(sog.entity_id)']
                        )->group('main_table.entity_id');
                    }
                    $collection->getSelect()->joinLeft(
                        ['mo' => 'marketplace_orders'],
                        'sog.entity_id = mo.order_id',
                        []
                    )->where('mo.seller_id IN (?) OR mo.seller_id IS NULL', $rule->getSellers());
                }
                break;
        }
    }
}
