<?php
declare(strict_types=1);

namespace Branch8\ProductCertification\Controller\Adminhtml\Type;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

/**
 * Admin: Certification Type listing page
 */
class Index extends Action
{
    public const ADMIN_RESOURCE = 'Branch8_ProductCertification::certification_manage';

    /**
     * @var PageFactory
     */
    private PageFactory $resultPageFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Branch8_ProductCertification::certification_manage');
        $resultPage->getConfig()->getTitle()->prepend(__('Certification Types'));
        return $resultPage;
    }
}
