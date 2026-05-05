<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Model\Restriction\Order\Collection;

use Amasty\Rolepermissions\Helper\Data as Helper;
use Magento\Sales\Model\ResourceModel\Order\Collection as OrderCollection;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as OrderGridCollection;
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

    public function execute(OrderCollection|OrderGridCollection $collection): void
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

    public function restrictOrderCollection($rule, OrderCollection|OrderGridCollection $collection): void
    {
        switch ($rule->getSellerAccessMode()) {
            case Seller::MODE_ANY:
                break;
            case Seller::MODE_SELECTED:
                if ($rule->getSellers()) {
                    $sellerIds = array_map('intval', $rule->getSellers());
                    $sellerIds = array_filter($sellerIds);

                    if (empty($sellerIds)) {
                        $collection->getSelect()->where('1=0');
                        break;
                    }

                    $select = $collection->getSelect();
                    $fromPart = $select->getPart(\Magento\Framework\DB\Select::FROM);

                    if (isset($fromPart['mo'])) {
                        $select->where('mo.seller_id IN (?)', $sellerIds);
                    } else {
                        $sellerIdsCsv = implode(',', $sellerIds);
                        $subQuery = new \Zend_Db_Expr(
                            '(SELECT DISTINCT order_id, seller_id from marketplace_orders where seller_id in (' . $sellerIdsCsv . '))'
                        );
                        $select->join(
                            ['mo' => $subQuery],
                            'main_table.entity_id = mo.order_id',
                            []
                        );
                    }
                }
                break;
        }
    }
}
