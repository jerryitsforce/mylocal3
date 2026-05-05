<?php

namespace Branch8\Bookmark\Model\ResourceModel\SellerBookmark;

use Branch8\Bookmark\Model\ResourceModel\SellerBookmark as ResourceModel;
use Branch8\Bookmark\Model\SellerBookmark as Model;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Initialize collection model.
     */
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
