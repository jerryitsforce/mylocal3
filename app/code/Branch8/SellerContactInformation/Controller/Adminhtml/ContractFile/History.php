<?php
namespace Branch8\SellerContactInformation\Controller\Adminhtml\ContractFile;

class History extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Magento_Customer::manage';

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_pageFactory;

    protected $_conn;
    /**
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
       \Magento\Backend\App\Action\Context $context,
       \Magento\Framework\View\Result\PageFactory $pageFactory,
       \Magento\Framework\App\ResourceConnection $resourceConnection
    )
    {
        $this->_pageFactory = $pageFactory;
        parent::__construct($context);
        $this->_conn = $resourceConnection->getConnection();
    }

    /**
     * Index action
     *
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->_pageFactory->create();
        $sellerId = $this->getRequest()->getParam('seller_id', 0);
        $sql = 'select name from customer_grid_flat where entity_id = '.$sellerId;
        $sellerName = $this->_conn->fetchOne($sql);
        $resultPage->getConfig()->getTitle()->prepend(__('Seller contract history - %1', $sellerName));

        return $resultPage;
    }

    /**
     * Is the user allowed to view the page.
    *
    * @return bool
    */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(static::ADMIN_RESOURCE);
    }
}
