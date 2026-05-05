<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Branch8\CatalogInventory\Rewrite\Magento\Bundle\Block\Catalog\Product\View\Type\Bundle\Option;

class Multi extends \Branch8\CatalogInventory\Rewrite\Magento\Bundle\Block\Catalog\Product\View\Type\Bundle\Option
{
    protected $_template = 'Branch8_CatalogInventory::stockqty/bundle/multi.phtml';

    /**
     * @inheritdoc
     * @since 100.2.0
     */
    protected function assignSelection(\Magento\Bundle\Model\Option $option, $selectionId)
    {
        if (is_array($selectionId)) {
            foreach ($selectionId as $id) {
                if ($id && $option->getSelectionById($id)) {
                    $this->_selectedOptions[] = $id;
                }
            }
        } else {
            parent::assignSelection($option, $selectionId);
        }
    }
}