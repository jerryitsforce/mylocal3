<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Branch8\MarketPlaceSeller\Override\Model\ResourceModel\Saleslist\Grid;

use Webkul\Marketplace\Model\ResourceModel\Saleslist\Grid\Collection as SaleslistCollection;

/**
 * Collection for displaying grid of marketplace saleslist
 */

class Collection extends SaleslistCollection
{
    /**
     * @inheritdoc
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->addFilterToMap('seller_id', 'main_table.seller_id');
    }
}
