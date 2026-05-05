<?php

namespace Branch8\Bookmark\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Ui\Model\ResourceModel\Bookmark;

class SellerBookmark extends Bookmark
{
    public function __construct(\Magento\Framework\Model\ResourceModel\Db\Context $context, $connectionName = null)
    {
        parent::__construct($context, $connectionName);
    }

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('seller_ui_bookmark', 'bookmark_id');
    }
}
