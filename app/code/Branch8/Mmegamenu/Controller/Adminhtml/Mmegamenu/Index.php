<?php
/**
 *
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Branch8\Mmegamenu\Controller\Adminhtml\Mmegamenu;

use Magento\Backend\App\Action;

class Index extends \Branch8\Mmegamenu\Controller\Adminhtml\Mmegamenu
{
    /**
     * Index action
     *
     * @return void
     */
    public function execute()
    {
        $this->_initAction();
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Megamenu'));
        $this->_view->renderLayout();
    }
}
