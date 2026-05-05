<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Permissions for Magento 2
 */

namespace Branch8\AdvancedPermissions\Model\ResourceModel\Customer\Grid;

use Magento\Customer\Ui\Component\DataProvider\Document;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface as Logger;

class Collection extends \Magento\Customer\Model\ResourceModel\Grid\Collection
{
    /**
     * @inheritdoc
     */
    protected function _initSelect()
    {
        //$this->addFilterToMap('order_latest_order_ids', 'order_index.increment_id');
        parent::_initSelect();
    }

    /**
     * Modify for website_id code same in location type and location list
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ($field === 'main_table.order_latest_order_ids') {
            $this->getSelect()->join(
                [
                    'order_index' => 'customer_orders_index'],
                'main_table.entity_id = order_index.customer_id'
            )->group('main_table.entity_id');
            $field = 'order_index.increment_id';
        }
        return parent::addFieldToFilter($field, $condition);
    }
}
