<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Branch8\Mmegamenu\Controller\Adminhtml;
abstract class Mmegamenu extends \Magento\Backend\App\Action
{
    /**
     * Init actions
     *
     * @return $this
     */
    protected function _initAction()
    {
        // load layout, set active menu and breadcrumbs
        $this->_view->loadLayout();
        $this->_setActiveMenu(
            'Branch8_Mmegamenu::mmegamenu_manage'
        )->_addBreadcrumb(
            __('Mmegamenu'),
            __('Mmegamenu')
        );
        return $this;
    }

    /**
     * Check the permission to run it
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Branch8_Mmegamenu::megamenu');
    }
}
