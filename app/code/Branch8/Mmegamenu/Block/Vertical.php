<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Branch8\Mmegamenu\Block;

/**
 * Main contact form block
 */
class Vertical extends Abstractmenu
{
    /**
     * Get menu items
     * @return mixed
     */
    public function getMegamenuItems()
    {
        $store = $this->getStore();
        return $this->getModel('Branch8\Mmegamenu\Model\Mmegamenu')
            ->getCollection()
            ->addStoreFilter($store)
            ->addFieldToFilter('parent_id', $this->getMenuId())
            ->addFieldToFilter('status', 1)
            ->setOrder('position', 'ASC');
    }
}

