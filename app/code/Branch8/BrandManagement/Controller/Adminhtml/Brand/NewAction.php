<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Branch8\BrandManagement\Controller\Adminhtml\Brand;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Branch8\BrandManagement\Controller\Adminhtml\Brand;

/**
 * Brand New Action Controller
 */
class NewAction extends Brand
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Branch8\BrandManagement\Api\BrandOptionRepositoryInterface $brandOptionRepository
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Branch8\BrandManagement\Api\BrandOptionRepositoryInterface $brandOptionRepository
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context, $coreRegistry, $brandOptionRepository);
    }

    /**
     * New action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Branch8_BrandManagement::brand_management');
        $resultPage->getConfig()->getTitle()->prepend(__('New Brand'));

        return $resultPage;
    }
}
