<?php
declare(strict_types=1);

namespace Branch8\HelpDesk\Controller\Adminhtml;

use Branch8\HelpDesk\Model\Ticket\AclRole;

/**
 * Abstract Class Category
 */
abstract class Category extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = AclRole::MANAGE_CATEGORY;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry
    )
    {
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context);
    }

    /**
     * Init page
     *
     * @param \Magento\Backend\Model\View\Result\Page $resultPage
     * @return \Magento\Backend\Model\View\Result\Page
     */
    protected function initPage($resultPage)
    {
        $resultPage->setActiveMenu('Branch8_HelpDesk::category')
            ->addBreadcrumb(__('Helpdesk'), __('HelpDesk'))
            ->addBreadcrumb(__('Category'), __('Category'));
        return $resultPage;
    }
}
