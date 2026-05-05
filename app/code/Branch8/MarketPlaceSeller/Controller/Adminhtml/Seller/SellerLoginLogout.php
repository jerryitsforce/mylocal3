<?php

namespace Branch8\MarketPlaceSeller\Controller\Adminhtml\Seller;

class SellerLoginLogout extends Logging
{
    const ADMIN_RESOURCE = 'Branch8_MarketPlaceSeller::b8_logging_seller_login_logout';

    protected $resultPageFactory = false;
    public function __construct(
        \Magento\Backend\App\Action\Context                $context,
        \Magento\Framework\Registry                      $coreRegistry,
        \Magento\Logging\Model\EventFactory              $eventFactory,
        \Magento\Logging\Model\ArchiveFactory            $archiveFactory,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    )
    {
        parent::__construct($context, $coreRegistry, $eventFactory, $archiveFactory, $fileFactory);
        $this->_coreRegistry = $coreRegistry;
        $this->_eventFactory = $eventFactory;
        $this->_archiveFactory = $archiveFactory;
        $this->_fileFactory = $fileFactory;
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend((__('Seller login/ logout Report')));

        return $resultPage;
    }
}